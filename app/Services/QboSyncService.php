<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Installer;
use App\Models\Invoice;
use App\Models\QuickReturn;
use App\Models\Vendor;
use App\Models\VendorCreditMemo;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

class QboSyncService
{
    public function __construct(private QuickBooksService $qbo) {}

    // =========================================================================
    // Vendors
    // =========================================================================

    /**
     * Push a vendor to QBO. Creates if no qbo_id, updates if one exists.
     * Returns ['success' => bool, 'message' => string, 'qbo_id' => string|null]
     */
    public function pushVendor(Vendor $vendor): array
    {
        try {
            $payload = $this->buildVendorPayload($vendor);

            if ($vendor->qbo_id) {
                // Update existing QBO vendor — must include Id + SyncToken
                $payload['Id']        = $vendor->qbo_id;
                $payload['SyncToken'] = $vendor->qbo_sync_token ?? '0';
                $response = $this->qbo->post('vendor', $payload);
                $qboVendor = $response['Vendor'];
                $action = 'updated';
            } else {
                // Check if a vendor with this name already exists in QBO
                $existing = $this->findQboVendorByName($vendor->company_name);

                if ($existing) {
                    // Link to the existing QBO vendor instead of creating a duplicate
                    $qboVendor = $existing;
                    $action = 'linked';
                } else {
                    try {
                        $response = $this->qbo->post('vendor', $payload);
                        $qboVendor = $response['Vendor'];
                        $action = 'created';
                    } catch (\RuntimeException $e) {
                        $duplicate = $this->fetchQboDuplicateVendor($e->getMessage(), $vendor->company_name);
                        if ($duplicate) {
                            $qboVendor = $duplicate;
                            $action    = 'linked';
                        } else {
                            throw new \RuntimeException($this->buildDuplicateNameError($e->getMessage(), $vendor->company_name));
                        }
                    }
                }
            }

            $vendor->update([
                'qbo_id'         => $qboVendor['Id'],
                'qbo_sync_token' => $qboVendor['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('vendor', $vendor->id, 'push', 'success', $qboVendor['Id'],
                ucfirst($action) . ' vendor in QBO', $payload, $qboVendor);

            return ['success' => true, 'message' => 'Vendor ' . $action . ' in QuickBooks.', 'qbo_id' => $qboVendor['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('vendor', $vendor->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    private function buildVendorPayload(Vendor $vendor): array
    {
        $payload = [
            'DisplayName' => $vendor->company_name,
        ];

        if ($vendor->contact_name) {
            $parts = explode(' ', trim($vendor->contact_name), 2);
            $payload['GivenName']  = $parts[0] ?? '';
            $payload['FamilyName'] = $parts[1] ?? '';
        }

        if ($vendor->email) {
            $payload['PrimaryEmailAddr'] = ['Address' => $vendor->email];
        }

        if ($vendor->phone) {
            $payload['PrimaryPhone'] = ['FreeFormNumber' => $vendor->phone];
        }

        if ($vendor->mobile) {
            $payload['Mobile'] = ['FreeFormNumber' => $vendor->mobile];
        }

        if ($vendor->website) {
            $payload['WebAddr'] = ['URI' => $vendor->website];
        }

        if ($vendor->address || $vendor->city) {
            $payload['BillAddr'] = [
                'Line1'                  => $vendor->address ?? '',
                'Line2'                  => $vendor->address2 ?? '',
                'City'                   => $vendor->city ?? '',
                'CountrySubDivisionCode' => $vendor->province ?? '',
                'PostalCode'             => $vendor->postal_code ?? '',
                'Country'                => 'CA',
            ];
        }

        if ($vendor->account_number) {
            $payload['AcctNum'] = $vendor->account_number;
        }

        return $payload;
    }

    private function findQboVendorByName(string $name): ?array
    {
        $escapedName = addslashes($name);
        $result = $this->qbo->query("SELECT * FROM Vendor WHERE DisplayName = '{$escapedName}'");
        $vendors = $result['QueryResponse']['Vendor'] ?? [];
        return $vendors[0] ?? null;
    }

    /**
     * When QBO returns a 6240 duplicate-name error, find the conflicting vendor
     * (including inactive ones), reactivate if needed, and return it.
     *
     * Three-step fallback because:
     * - GET by Id fails for inactive vendors (QBO returns 610)
     * - Query by DisplayName fails when the name in FM differs from QBO
     * - Last resort: page through all vendors (active+inactive) and match by Id
     */
    private function fetchQboDuplicateVendor(string $errorMessage, string $displayName): ?array
    {
        if (! preg_match('/"code"\s*:\s*"6240"/', $errorMessage)) {
            return null;
        }
        if (! preg_match('/Id=(\d+)/', $errorMessage, $m)) {
            return null;
        }
        $qboId = $m[1];

        // Step 1: direct GET — works when vendor is active
        try {
            $response = $this->qbo->get('vendor/' . $qboId);
            if (! empty($response['Vendor'])) {
                return $this->ensureVendorActive($response['Vendor']);
            }
        } catch (\RuntimeException) {
            // inactive vendor — fall through
        }

        // Step 2: query by name including inactive — works when name matches exactly
        $escaped = addslashes($displayName);
        $result  = $this->qbo->query("SELECT * FROM Vendor WHERE DisplayName = '{$escaped}' AND Active IN (true,false) STARTPOSITION 1 MAXRESULTS 1");
        $vendors = $result['QueryResponse']['Vendor'] ?? [];
        if (! empty($vendors)) {
            return $this->ensureVendorActive($vendors[0]);
        }

        // Step 3: page through all vendors (active+inactive) and match by Id
        // — needed when FM name differs from QBO DisplayName
        $startPos = 1;
        $pageSize = 500;
        do {
            $result = $this->qbo->query("SELECT Id, DisplayName, SyncToken, Active FROM Vendor WHERE Active IN (true,false) STARTPOSITION {$startPos} MAXRESULTS {$pageSize}");
            $page   = $result['QueryResponse']['Vendor'] ?? [];
            foreach ($page as $v) {
                if ((string) $v['Id'] === $qboId) {
                    return $this->ensureVendorActive($v);
                }
            }
            $startPos += $pageSize;
        } while (count($page) === $pageSize);

        return null;
    }

    private function buildDuplicateNameError(string $rawError, string $displayName): string
    {
        if (preg_match('/Id=(\d+)/', $rawError, $m)) {
            return "The name \"{$displayName}\" conflicts with a deleted QuickBooks vendor (Id={$m[1]}) that can no longer be accessed via the API. To fix: in QuickBooks go to Expenses → Vendors → click the gear icon → check \"Include inactive\" → find this vendor → make it active. Then sync again.";
        }
        return $rawError;
    }

    private function ensureVendorActive(array $qboVendor): array
    {
        if (($qboVendor['Active'] ?? true) === false) {
            $response  = $this->qbo->post('vendor', [
                'Id'        => $qboVendor['Id'],
                'SyncToken' => $qboVendor['SyncToken'],
                'Active'    => true,
            ]);
            $qboVendor = $response['Vendor'];
        }
        return $qboVendor;
    }

    // =========================================================================
    // Installers (pushed to QBO as Vendors / subcontractors)
    // =========================================================================

    public function pushInstaller(Installer $installer): array
    {
        try {
            $payload = [
                'DisplayName'     => $installer->company_name,
                'Vendor1099'      => true,
            ];

            if ($installer->contact_name) {
                $parts = explode(' ', trim($installer->contact_name), 2);
                $payload['GivenName']  = $parts[0] ?? '';
                $payload['FamilyName'] = $parts[1] ?? '';
            }

            if ($installer->email) {
                $payload['PrimaryEmailAddr'] = ['Address' => $installer->email];
            }

            if ($installer->phone) {
                $payload['PrimaryPhone'] = ['FreeFormNumber' => $installer->phone];
            }

            if ($installer->address || $installer->city) {
                $payload['BillAddr'] = [
                    'Line1'                  => $installer->address ?? '',
                    'City'                   => $installer->city ?? '',
                    'CountrySubDivisionCode' => $installer->province ?? '',
                    'PostalCode'             => $installer->postal_code ?? '',
                    'Country'                => 'CA',
                ];
            }

            if ($installer->qbo_id) {
                $payload['Id']        = $installer->qbo_id;
                $payload['SyncToken'] = $installer->qbo_sync_token ?? '0';
                $response   = $this->qbo->post('vendor', $payload);
                $qboVendor  = $response['Vendor'];
                $action     = 'updated';
            } else {
                $existing = $this->findQboVendorByName($installer->company_name);
                if ($existing) {
                    $qboVendor = $existing;
                    $action    = 'linked';
                } else {
                    try {
                        $response  = $this->qbo->post('vendor', $payload);
                        $qboVendor = $response['Vendor'];
                        $action    = 'created';
                    } catch (\RuntimeException $e) {
                        $duplicate = $this->fetchQboDuplicateVendor($e->getMessage(), $installer->company_name);
                        if ($duplicate) {
                            $qboVendor = $duplicate;
                            $action    = 'linked';
                        } else {
                            throw new \RuntimeException($this->buildDuplicateNameError($e->getMessage(), $installer->company_name));
                        }
                    }
                }
            }

            $installer->update([
                'qbo_id'         => $qboVendor['Id'],
                'qbo_sync_token' => $qboVendor['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('installer', $installer->id, 'push', 'success', $qboVendor['Id'],
                ucfirst($action) . ' installer as vendor in QBO', $payload, $qboVendor);

            return ['success' => true, 'message' => 'Installer ' . $action . ' in QuickBooks.', 'qbo_id' => $qboVendor['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('installer', $installer->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    // =========================================================================
    // Customers
    // =========================================================================

    /**
     * Push a customer (parent or job site) to QBO.
     * If the customer has a parent_id, the parent is pushed first to get its QBO ID.
     */
    public function pushCustomer(Customer $customer): array
    {
        try {
            // If this is a job site (has a parent), ensure parent is synced first
            $parentQboId = null;
            if ($customer->parent_id) {
                $parent = $customer->parent;
                if (! $parent->qbo_id) {
                    $parentResult = $this->pushCustomer($parent);
                    if (! $parentResult['success']) {
                        return ['success' => false, 'message' => 'Failed to sync parent customer: ' . $parentResult['message'], 'qbo_id' => null];
                    }
                    $parent->refresh();
                }
                $parentQboId = $parent->qbo_id;
            }

            $payload = $this->buildCustomerPayload($customer, $parentQboId);

            if ($customer->qbo_id) {
                $payload['Id']        = $customer->qbo_id;
                $payload['SyncToken'] = $customer->qbo_sync_token ?? '0';
                $response = $this->qbo->post('customer', $payload);
                $qboCustomer = $response['Customer'];
                $action = 'updated';
            } else {
                $existing = $this->findQboCustomerByName($this->customerDisplayName($customer));

                if ($existing) {
                    $qboCustomer = $existing;
                    $action = 'linked';
                } else {
                    $response = $this->qbo->post('customer', $payload);
                    $qboCustomer = $response['Customer'];
                    $action = 'created';
                }
            }

            $customer->update([
                'qbo_id'         => $qboCustomer['Id'],
                'qbo_sync_token' => $qboCustomer['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('customer', $customer->id, 'push', 'success', $qboCustomer['Id'],
                ucfirst($action) . ' customer in QBO', $payload, $qboCustomer);

            return ['success' => true, 'message' => 'Customer ' . $action . ' in QuickBooks.', 'qbo_id' => $qboCustomer['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('customer', $customer->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    private function buildCustomerPayload(Customer $customer, ?string $parentQboId): array
    {
        $payload = [
            'DisplayName' => $this->customerDisplayName($customer),
        ];

        if ($customer->name || $customer->company_name) {
            $name = $customer->name ?? $customer->company_name;
            $parts = explode(' ', trim($name), 2);
            $payload['GivenName']  = $parts[0] ?? '';
            $payload['FamilyName'] = $parts[1] ?? '';
        }

        if ($customer->company_name) {
            $payload['CompanyName'] = $customer->company_name;
        }

        if ($customer->email) {
            $payload['PrimaryEmailAddr'] = ['Address' => $customer->email];
        }

        if ($customer->phone) {
            $payload['PrimaryPhone'] = ['FreeFormNumber' => $customer->phone];
        }

        if ($customer->mobile) {
            $payload['Mobile'] = ['FreeFormNumber' => $customer->mobile];
        }

        if ($customer->address || $customer->city) {
            $payload['BillAddr'] = [
                'Line1'                  => $customer->address ?? '',
                'Line2'                  => $customer->address2 ?? '',
                'City'                   => $customer->city ?? '',
                'CountrySubDivisionCode' => $customer->province ?? '',
                'PostalCode'             => $customer->postal_code ?? '',
                'Country'                => 'CA',
            ];
        }

        // Sub-customer (job site) — link to parent
        if ($parentQboId) {
            $payload['Job']       = true;
            $payload['ParentRef'] = ['value' => $parentQboId];
        }

        return $payload;
    }

    /**
     * QBO requires DisplayName to be unique. For job sites, QBO builds
     * the "Parent:Child" display automatically from ParentRef — we just
     * need a unique name for the child record itself.
     */
    private function customerDisplayName(Customer $customer): string
    {
        $name = $customer->company_name ?: $customer->name;

        // If this is a job site with the same name as the parent, make it unique
        if ($customer->parent_id && $customer->parent) {
            $parentName = $customer->parent->company_name ?: $customer->parent->name;
            if ($name === $parentName) {
                return $name . ' (Site)';
            }
        }

        return $name;
    }

    private function findQboCustomerByName(string $displayName): ?array
    {
        $escapedName = addslashes($displayName);
        $result = $this->qbo->query("SELECT * FROM Customer WHERE DisplayName = '{$escapedName}'");
        $customers = $result['QueryResponse']['Customer'] ?? [];
        return $customers[0] ?? null;
    }

    // =========================================================================
    // Bills (AP)
    // =========================================================================

    /**
     * Push a bill to QBO as a Bill entity.
     * Vendor must be synced first (or will be auto-synced).
     * $accountIds = ['product' => QBO ID, 'freight' => QBO ID, 'labour' => QBO ID]
     */
    public function pushBill(Bill $bill, array $accountIds): array
    {
        try {
            $bill->load(['vendor', 'installer', 'items.purchaseOrderItem.saleItem']);

            // Resolve the QBO vendor reference — vendor bill uses Vendor, installer bill uses Installer (synced as QBO Vendor)
            if ($bill->bill_type === 'vendor') {
                $vendor = $bill->vendor;
                if (! $vendor) {
                    return ['success' => false, 'message' => 'Bill has no vendor assigned.', 'qbo_id' => null];
                }
                if (! $vendor->qbo_id) {
                    $result = $this->pushVendor($vendor);
                    if (! $result['success']) {
                        return ['success' => false, 'message' => 'Failed to sync vendor: ' . $result['message'], 'qbo_id' => null];
                    }
                    $vendor->refresh();
                }
                $vendorQboId = $vendor->qbo_id;
            } else {
                $installer = $bill->installer;
                if (! $installer) {
                    return ['success' => false, 'message' => 'Bill has no installer assigned.', 'qbo_id' => null];
                }
                if (! $installer->qbo_id) {
                    $result = $this->pushInstaller($installer);
                    if (! $result['success']) {
                        return ['success' => false, 'message' => 'Failed to sync installer: ' . $result['message'], 'qbo_id' => null];
                    }
                    $installer->refresh();
                }
                $vendorQboId = $installer->qbo_id;
            }

            $payload = $this->buildBillPayload($bill, $vendorQboId, $accountIds);

            if ($bill->qbo_id) {
                $payload['Id']        = $bill->qbo_id;
                $payload['SyncToken'] = $bill->qbo_sync_token ?? '0';
                $response = $this->qbo->post('bill', $payload);
                $qboBill = $response['Bill'];
                $action = 'updated';
            } else {
                $response = $this->qbo->post('bill', $payload);
                $qboBill = $response['Bill'];
                $action = 'created';
            }

            $bill->update([
                'qbo_id'         => $qboBill['Id'],
                'qbo_sync_token' => $qboBill['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('bill', $bill->id, 'push', 'success', $qboBill['Id'],
                ucfirst($action) . ' bill in QBO', $payload, $qboBill);

            return ['success' => true, 'message' => 'Bill ' . $action . ' in QuickBooks.', 'qbo_id' => $qboBill['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('bill', $bill->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    private function buildBillPayload(Bill $bill, string $vendorQboId, array $accountIds): array
    {
        $lines = [];

        foreach ($bill->items as $item) {
            // Charge/credit rows resolve by charge_type; regular rows by sale item type or bill type
            if ($item->charge_type) {
                $accountId = match ($item->charge_type) {
                    'fuel', 'freight'              => $accountIds['freight'],
                    'early_payment', 'other_credit' => $accountIds['credit'] ?? $accountIds['product'],
                    default                         => $accountIds['product'],
                };
            } elseif ($bill->bill_type === 'installer') {
                $accountId = $accountIds['labour'];
            } else {
                $saleItemType = $item->purchaseOrderItem?->saleItem?->type ?? 'material';
                $accountId = match ($saleItemType) {
                    'freight' => $accountIds['freight'],
                    'labour'  => $accountIds['labour'],
                    default   => $accountIds['product'],
                };
            }

            // Tax code: GST+PST for vendor bills with PST, GST-only otherwise
            $hasPst     = $bill->pst_amount > 0;
            $taxCodeId  = ($hasPst && ($accountIds['gst_pst_code_id'] ?? null))
                ? $accountIds['gst_pst_code_id']
                : ($accountIds['gst_code_id'] ?? null);

            $lineDetail = [
                'AccountRef'     => ['value' => $accountId],
                'BillableStatus' => 'NotBillable',
            ];
            if ($taxCodeId) {
                $lineDetail['TaxCodeRef'] = ['value' => $taxCodeId];
            }

            $lines[] = [
                'Amount'      => (float) $item->line_total,
                'DetailType'  => 'AccountBasedExpenseLineDetail',
                'Description' => $item->item_name . ($item->quantity ? ' (Qty: ' . $item->quantity . ' @ $' . number_format($item->unit_cost, 2) . ')' : ''),
                'AccountBasedExpenseLineDetail' => $lineDetail,
            ];
        }

        // Build TxnTaxDetail so QBO shows proper GST/PST instead of expense lines
        $taxLines = [];
        $gstRateId = $accountIds['gst_rate_id'] ?? null;
        $pstRateId = $accountIds['pst_rate_id'] ?? null;

        if ($gstRateId && $bill->gst_amount > 0) {
            $taxLines[] = [
                'Amount'        => (float) $bill->gst_amount,
                'DetailType'    => 'TaxLineDetail',
                'TaxLineDetail' => [
                    'TaxRateRef'        => ['value' => $gstRateId],
                    'PercentBased'      => false,
                    'NetAmountTaxable'  => (float) $bill->subtotal,
                ],
            ];
        }

        if ($pstRateId && $bill->pst_amount > 0) {
            $taxLines[] = [
                'Amount'        => (float) $bill->pst_amount,
                'DetailType'    => 'TaxLineDetail',
                'TaxLineDetail' => [
                    'TaxRateRef'        => ['value' => $pstRateId],
                    'PercentBased'      => false,
                    'NetAmountTaxable'  => (float) $bill->subtotal,
                ],
            ];
        }

        $payload = [
            'VendorRef'             => ['value' => $vendorQboId],
            'TxnDate'               => $bill->bill_date->toDateString(),
            'DocNumber'             => $bill->reference_number,
            'GlobalTaxCalculation'  => 'TaxExcluded',
            'Line'                  => $lines,
        ];

        if ($taxLines) {
            $payload['TxnTaxDetail'] = [
                'TotalTax' => (float) $bill->tax_amount,
                'TaxLine'  => $taxLines,
            ];
        }

        if ($bill->due_date) {
            $payload['DueDate'] = $bill->due_date->toDateString();
        }

        if ($bill->notes) {
            $payload['PrivateNote'] = $bill->notes;
        }

        return $payload;
    }

    // =========================================================================
    // Vendor Credit Memos (AP credit)
    // =========================================================================

    /**
     * Push a VendorCreditMemo to QBO as a VendorCredit entity.
     * Vendor is auto-synced if not already in QBO.
     * $accountIds must contain at least a 'product' key (QBO expense account ID).
     */
    public function pushVendorCredit(VendorCreditMemo $credit, array $accountIds): array
    {
        try {
            $credit->load('vendor');

            $vendor = $credit->vendor;
            if (! $vendor) {
                return ['success' => false, 'message' => 'Credit memo has no vendor assigned.', 'qbo_id' => null];
            }

            if (! $vendor->qbo_id) {
                $vendorResult = $this->pushVendor($vendor);
                if (! $vendorResult['success']) {
                    return ['success' => false, 'message' => 'Failed to sync vendor: ' . $vendorResult['message'], 'qbo_id' => null];
                }
                $vendor->refresh();
            }

            $payload = $this->buildVendorCreditPayload($credit, $vendor->qbo_id, $accountIds);

            if ($credit->qbo_id) {
                $payload['Id']        = $credit->qbo_id;
                $payload['SyncToken'] = $credit->qbo_sync_token ?? '0';
                $response  = $this->qbo->post('vendorcredit', $payload);
                $qboCredit = $response['VendorCredit'];
                $action    = 'updated';
            } else {
                $response  = $this->qbo->post('vendorcredit', $payload);
                $qboCredit = $response['VendorCredit'];
                $action    = 'created';
            }

            $credit->update([
                'qbo_id'         => $qboCredit['Id'],
                'qbo_sync_token' => $qboCredit['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('vendor_credit', $credit->id, 'push', 'success', $qboCredit['Id'],
                ucfirst($action) . ' vendor credit in QBO', $payload, $qboCredit);

            return ['success' => true, 'message' => 'Vendor credit ' . $action . ' in QuickBooks.', 'qbo_id' => $qboCredit['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('vendor_credit', $credit->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    private function buildVendorCreditPayload(VendorCreditMemo $credit, string $vendorQboId, array $accountIds): array
    {
        $lines = [];

        // Tax code for the subtotal line
        $hasPst        = $credit->pst_amount > 0;
        $taxCodeId     = ($hasPst && ($accountIds['gst_pst_code_id'] ?? null))
            ? $accountIds['gst_pst_code_id']
            : ($accountIds['gst_code_id'] ?? null);

        $lineDetail = ['AccountRef' => ['value' => $accountIds['product']]];
        if ($taxCodeId) {
            $lineDetail['TaxCodeRef'] = ['value' => $taxCodeId];
        }

        // Subtotal line
        $lines[] = [
            'Amount'      => (float) $credit->subtotal,
            'DetailType'  => 'AccountBasedExpenseLineDetail',
            'Description' => 'Credit ' . $credit->credit_memo_number,
            'AccountBasedExpenseLineDetail' => $lineDetail,
        ];

        $taxLines  = [];
        $gstRateId = $accountIds['gst_rate_id'] ?? null;
        $pstRateId = $accountIds['pst_rate_id'] ?? null;

        if ($gstRateId && $credit->gst_amount > 0) {
            $taxLines[] = [
                'Amount'        => (float) $credit->gst_amount,
                'DetailType'    => 'TaxLineDetail',
                'TaxLineDetail' => [
                    'TaxRateRef'       => ['value' => $gstRateId],
                    'PercentBased'     => false,
                    'NetAmountTaxable' => (float) $credit->subtotal,
                ],
            ];
        }

        if ($pstRateId && $credit->pst_amount > 0) {
            $taxLines[] = [
                'Amount'        => (float) $credit->pst_amount,
                'DetailType'    => 'TaxLineDetail',
                'TaxLineDetail' => [
                    'TaxRateRef'       => ['value' => $pstRateId],
                    'PercentBased'     => false,
                    'NetAmountTaxable' => (float) $credit->subtotal,
                ],
            ];
        }

        $payload = [
            'VendorRef'            => ['value' => $vendorQboId],
            'TxnDate'              => $credit->date->toDateString(),
            'DocNumber'            => $credit->reference_number ?: $credit->credit_memo_number,
            'GlobalTaxCalculation' => 'TaxExcluded',
            'Line'                 => $lines,
        ];

        if ($taxLines) {
            $payload['TxnTaxDetail'] = [
                'TotalTax' => (float) $credit->tax_amount,
                'TaxLine'  => $taxLines,
            ];
        }

        if ($credit->notes) {
            $payload['PrivateNote'] = $credit->notes;
        }

        return $payload;
    }

    // =========================================================================
    // Invoices (AR)
    // =========================================================================

    /**
     * Push an invoice to QBO as an Invoice entity.
     * Customer (job site) is auto-synced if not already in QBO.
     * $itemIds = ['material' => QBO ID, 'freight' => QBO ID, 'labour' => QBO ID]
     */
    public function pushInvoice(Invoice $invoice, array $itemIds): array
    {
        try {
            $invoice->load(['rooms.items', 'sale.opportunity.jobSiteCustomer.parent', 'sale.opportunity.parentCustomer']);

            $sale        = $invoice->sale;
            $opportunity = $sale?->opportunity;
            $jobSite     = $opportunity?->jobSiteCustomer;
            $parent      = $jobSite?->parent ?? $opportunity?->parentCustomer;
            $billTo      = $jobSite ?? $parent;

            if (! $billTo) {
                return ['success' => false, 'message' => 'Invoice has no customer linked.', 'qbo_id' => null];
            }

            // Ensure parent customer is synced first (only needed when billing to a job site sub-customer)
            if ($jobSite && $parent && ! $parent->qbo_id) {
                $parentResult = $this->pushCustomer($parent);
                if (! $parentResult['success']) {
                    return ['success' => false, 'message' => 'Failed to sync parent customer: ' . $parentResult['message'], 'qbo_id' => null];
                }
                $parent->refresh();
            }

            // Ensure bill-to customer is synced
            if (! $billTo->qbo_id) {
                $customerResult = $this->pushCustomer($billTo);
                if (! $customerResult['success']) {
                    return ['success' => false, 'message' => 'Failed to sync customer: ' . $customerResult['message'], 'qbo_id' => null];
                }
                $billTo->refresh();
            }

            $payload = $this->buildInvoicePayload($invoice, $billTo->qbo_id, $itemIds);

            if ($invoice->qbo_id) {
                $payload['Id']        = $invoice->qbo_id;
                $payload['SyncToken'] = $invoice->qbo_sync_token ?? '0';
                $response    = $this->qbo->post('invoice', $payload);
                $qboInvoice  = $response['Invoice'];
                $action      = 'updated';
            } else {
                $response    = $this->qbo->post('invoice', $payload);
                $qboInvoice  = $response['Invoice'];
                $action      = 'created';
            }

            $invoice->update([
                'qbo_id'         => $qboInvoice['Id'],
                'qbo_sync_token' => $qboInvoice['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('invoice', $invoice->id, 'push', 'success', $qboInvoice['Id'],
                ucfirst($action) . ' invoice in QBO', $payload, $qboInvoice);

            return ['success' => true, 'message' => 'Invoice ' . $action . ' in QuickBooks.', 'qbo_id' => $qboInvoice['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('invoice', $invoice->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    /**
     * Push an InvoicePayment to QBO. The linked invoice must already have a qbo_id.
     */
    public function pushPayment(\App\Models\InvoicePayment $payment): array
    {
        try {
            $payment->load(['invoice.sale.opportunity.jobSiteCustomer.parent', 'invoice.sale.opportunity.parentCustomer']);

            $invoice     = $payment->invoice;
            $sale        = $invoice->sale;
            $opportunity = $sale?->opportunity;
            $jobSite     = $opportunity?->jobSiteCustomer;
            $parent      = $jobSite?->parent ?? $opportunity?->parentCustomer;
            $billTo      = $jobSite ?? $parent;

            if (! $invoice->qbo_id) {
                return ['success' => false, 'message' => 'Invoice must be synced to QBO before pushing a payment.'];
            }

            if (! $billTo?->qbo_id) {
                return ['success' => false, 'message' => 'Customer must be synced to QBO before pushing a payment.'];
            }

            $payload = [
                'CustomerRef' => ['value' => $billTo->qbo_id],
                'TotalAmt'    => (float) $payment->amount,
                'TxnDate'     => $payment->payment_date->toDateString(),
                'Line'        => [[
                    'Amount'    => (float) $payment->amount,
                    'LinkedTxn' => [[
                        'TxnId'   => $invoice->qbo_id,
                        'TxnType' => 'Invoice',
                    ]],
                ]],
            ];

            $methodId = $this->mapPaymentMethod($payment->payment_method);
            if ($methodId) {
                $payload['PaymentMethodRef'] = ['value' => $methodId];
            }

            if ($payment->reference_number) {
                $payload['PaymentRefNum'] = $payment->reference_number;
            }

            if ($payment->notes) {
                $payload['PrivateNote'] = $payment->notes;
            }

            if ($payment->qbo_id) {
                $payload['Id']        = $payment->qbo_id;
                $payload['SyncToken'] = '0';
                $response   = $this->qbo->post('payment', $payload);
                $qboPayment = $response['Payment'];
                $action     = 'updated';
            } else {
                $response   = $this->qbo->post('payment', $payload);
                $qboPayment = $response['Payment'];
                $action     = 'created';
            }

            $payment->update([
                'qbo_id'        => $qboPayment['Id'],
                'qbo_synced_at' => now(),
            ]);

            $this->qbo->log('payment', $payment->id, 'push', 'success', $qboPayment['Id'],
                ucfirst($action) . ' payment in QBO', $payload, $qboPayment);

            return ['success' => true, 'message' => 'Payment ' . $action . ' in QuickBooks.'];

        } catch (\Exception $e) {
            $this->qbo->log('payment', $payment->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function mapPaymentMethod(string $method): ?string
    {
        return match ($method) {
            'cash'                  => '16',
            'cheque'                => '17',
            'e-transfer'            => '1000000001',
            'visa'                  => '20',
            'mastercard'            => '19',
            'other', 'credit_card'  => '21',
            default                 => null,
        };
    }

    // =========================================================================
    // Quick Returns (RefundReceipt)
    // =========================================================================

    /**
     * Push a QuickReturn to QBO as a RefundReceipt entity.
     * If the return has a linked Customer, it is auto-synced first.
     * If there is no customer record, $cashCustomerQboId is used instead.
     * $itemIds = ['material' => QBO Item ID]
     */
    public function pushQuickReturn(QuickReturn $return, array $itemIds, string $cashCustomerQboId, string $refundAccountId): array
    {
        try {
            $return->load(['customer', 'items']);

            // Resolve customer QBO ID
            if ($return->customer_id && $return->customer) {
                $customer = $return->customer;
                if (! $customer->qbo_id) {
                    $result = $this->pushCustomer($customer);
                    if (! $result['success']) {
                        return ['success' => false, 'message' => 'Failed to sync customer: ' . $result['message'], 'qbo_id' => null];
                    }
                    $customer->refresh();
                }
                $customerQboId = $customer->qbo_id;
            } else {
                $customerQboId = $cashCustomerQboId;
            }

            $payload = $this->buildRefundReceiptPayload($return, $customerQboId, $itemIds, $refundAccountId);

            if ($return->qbo_id) {
                $payload['Id']        = $return->qbo_id;
                $payload['SyncToken'] = $return->qbo_sync_token ?? '0';
                $response   = $this->qbo->post('refundreceipt', $payload);
                $qboReceipt = $response['RefundReceipt'];
                $action     = 'updated';
            } else {
                $response   = $this->qbo->post('refundreceipt', $payload);
                $qboReceipt = $response['RefundReceipt'];
                $action     = 'created';
            }

            $return->update([
                'qbo_id'         => $qboReceipt['Id'],
                'qbo_sync_token' => $qboReceipt['SyncToken'],
                'qbo_synced_at'  => now(),
            ]);

            $this->qbo->log('quick_return', $return->id, 'push', 'success', $qboReceipt['Id'],
                ucfirst($action) . ' refund receipt in QBO', $payload, $qboReceipt);

            return ['success' => true, 'message' => 'Refund receipt ' . $action . ' in QuickBooks.', 'qbo_id' => $qboReceipt['Id']];

        } catch (\Exception $e) {
            $this->qbo->log('quick_return', $return->id, 'push', 'error', null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qbo_id' => null];
        }
    }

    private function buildRefundReceiptPayload(QuickReturn $return, string $customerQboId, array $itemIds, string $refundAccountId): array
    {
        $lines     = [];
        $taxCodeId = $return->tax_rate_percent > 0 ? '39' : '25';

        foreach ($return->items as $item) {
            $lines[] = [
                'Amount'      => (float) $item->line_total,
                'DetailType'  => 'SalesItemLineDetail',
                'Description' => $item->description,
                'SalesItemLineDetail' => [
                    'ItemRef'    => ['value' => $itemIds['material']],
                    'Qty'        => (float) $item->quantity,
                    'UnitPrice'  => (float) $item->unit_price,
                    'TaxCodeRef' => ['value' => $taxCodeId],
                ],
            ];
        }

        $payload = [
            'CustomerRef'         => ['value' => $customerQboId],
            'DepositToAccountRef' => ['value' => $refundAccountId],
            'TxnDate'             => $return->created_at->toDateString(),
            'DocNumber'           => $return->return_number,
            'Line'                => $lines,
        ];

        $methodId = $this->mapPaymentMethod($return->refund_method);
        if ($methodId) {
            $payload['PaymentMethodRef'] = ['value' => $methodId];
        }

        if ($return->reference_number) {
            $payload['PaymentRefNum'] = $return->reference_number;
        }

        if ($return->notes) {
            $payload['PrivateNote'] = $return->notes;
        }

        return $payload;
    }

    // =========================================================================
    // Webhook handlers (QBO → FM)
    // =========================================================================

    /**
     * Called when QBO notifies us that a Bill entity was updated or deleted.
     * Fetches the current Bill from QBO and syncs payment status back to FM.
     */
    public function handleBillUpdate(string $qboId, string $operation): void
    {
        $bill = Bill::where('qbo_id', $qboId)->first();

        if (! $bill) {
            Log::info("[QBO Webhook] Bill qbo_id={$qboId} not found in FM — skipping.");
            return;
        }

        if ($operation === 'Delete') {
            // Bill deleted in QBO — clear the sync link so it can be re-pushed if needed
            $bill->update(['qbo_id' => null, 'qbo_sync_token' => null, 'qbo_synced_at' => null, 'qbo_paid_at' => null]);
            $this->qbo->log('bill', $bill->id, 'pull', 'success', $qboId, 'Bill deleted in QBO — sync link cleared');
            return;
        }

        try {
            $response = $this->qbo->get("bill/{$qboId}");
            $qboBill  = $response['Bill'] ?? null;

            if (! $qboBill) {
                Log::warning("[QBO Webhook] Bill #{$qboId} fetch returned empty response.");
                return;
            }

            $balance = (float) ($qboBill['Balance'] ?? 0);
            $updates = [
                'qbo_sync_token' => $qboBill['SyncToken'],
                'qbo_synced_at'  => now(),
            ];

            if ($balance <= 0 && ! $bill->qbo_paid_at) {
                // Fully paid in QBO
                $updates['qbo_paid_at'] = now();
                $updates['status']      = 'paid';
                $message = 'Bill marked as paid via QBO webhook';
            } elseif ($balance > 0 && $bill->qbo_paid_at) {
                // Payment was reversed in QBO
                $updates['qbo_paid_at'] = null;
                $message = 'Bill payment reversed in QBO — qbo_paid_at cleared';
            } else {
                $message = 'Bill updated in QBO (no payment status change)';
            }

            $bill->update($updates);

            $this->qbo->log('bill', $bill->id, 'pull', 'success', $qboId, $message);

        } catch (\Exception $e) {
            Log::error("[QBO Webhook] Failed to fetch Bill #{$qboId}: " . $e->getMessage());
            $this->qbo->log('bill', $bill->id, 'pull', 'error', $qboId, $e->getMessage());
        }
    }

    /**
     * Called when QBO notifies us that a BillPayment was created or deleted.
     * Fetches the payment from QBO, finds every linked Bill, and re-syncs each one's status.
     * This is the primary trigger when a bill is paid in QBO — QBO always fires BillPayment
     * events but may not consistently fire a Bill Update event.
     */
    public function handleBillPaymentUpdate(string $qboId, string $operation): void
    {
        if ($operation === 'Delete') {
            // When a payment is deleted the linked bills will also get Bill Update events,
            // so we don't need to do anything extra here.
            Log::info("[QBO Webhook] BillPayment #{$qboId} deleted — linked Bill Update events will handle the reversal.");
            return;
        }

        try {
            $response   = $this->qbo->get("billpayment/{$qboId}");
            $qboPayment = $response['BillPayment'] ?? null;

            if (! $qboPayment) {
                Log::warning("[QBO Webhook] BillPayment #{$qboId} fetch returned empty response.");
                return;
            }

            // Collect all linked Bill QBO IDs from the payment lines
            $linkedBillIds = [];
            foreach ($qboPayment['Line'] ?? [] as $line) {
                foreach ($line['LinkedTxn'] ?? [] as $txn) {
                    if (($txn['TxnType'] ?? '') === 'Bill' && ! empty($txn['TxnId'])) {
                        $linkedBillIds[] = $txn['TxnId'];
                    }
                }
            }

            if (empty($linkedBillIds)) {
                Log::info("[QBO Webhook] BillPayment #{$qboId} has no linked Bills.");
                return;
            }

            foreach ($linkedBillIds as $billQboId) {
                $this->handleBillUpdate($billQboId, 'Update');
            }

        } catch (\Exception $e) {
            Log::error("[QBO Webhook] Failed to fetch BillPayment #{$qboId}: " . $e->getMessage());
        }
    }

    /**
     * Called when QBO notifies us that a VendorCredit entity was updated or deleted.
     * If the credit's Balance = 0 it has been fully applied in QBO — mark FM status as applied.
     */
    public function handleVendorCreditUpdate(string $qboId, string $operation): void
    {
        $credit = VendorCreditMemo::where('qbo_id', $qboId)->first();

        if (! $credit) {
            Log::info("[QBO Webhook] VendorCredit qbo_id={$qboId} not found in FM — skipping.");
            return;
        }

        if ($operation === 'Delete') {
            $credit->update(['qbo_id' => null, 'qbo_sync_token' => null, 'qbo_synced_at' => null]);
            $this->qbo->log('vendor_credit', $credit->id, 'pull', 'success', $qboId, 'VendorCredit deleted in QBO — sync link cleared');
            return;
        }

        try {
            $response  = $this->qbo->get("vendorcredit/{$qboId}");
            $qboCredit = $response['VendorCredit'] ?? null;

            if (! $qboCredit) {
                Log::warning("[QBO Webhook] VendorCredit #{$qboId} fetch returned empty response.");
                return;
            }

            $balance = (float) ($qboCredit['Balance'] ?? 0);
            $updates = [
                'qbo_sync_token' => $qboCredit['SyncToken'],
                'qbo_synced_at'  => now(),
            ];

            if ($balance <= 0 && $credit->status === 'open') {
                $updates['status'] = 'applied';
                $message = 'Vendor credit marked as applied via QBO webhook';
            } elseif ($balance > 0 && $credit->status === 'applied') {
                $updates['status'] = 'open';
                $message = 'Vendor credit application reversed in QBO — status reset to open';
            } else {
                $message = 'VendorCredit updated in QBO (no status change)';
            }

            $credit->update($updates);

            $this->qbo->log('vendor_credit', $credit->id, 'pull', 'success', $qboId, $message);

        } catch (\Exception $e) {
            Log::error("[QBO Webhook] Failed to fetch VendorCredit #{$qboId}: " . $e->getMessage());
            $this->qbo->log('vendor_credit', $credit->id, 'pull', 'error', $qboId, $e->getMessage());
        }
    }

    /**
     * Called when QBO notifies us that an Invoice entity was updated or deleted.
     * Fetches the current Invoice from QBO and syncs payment status back to FM.
     */
    public function handleInvoiceUpdate(string $qboId, string $operation): void
    {
        $invoice = Invoice::where('qbo_id', $qboId)->first();

        if (! $invoice) {
            Log::info("[QBO Webhook] Invoice qbo_id={$qboId} not found in FM — skipping.");
            return;
        }

        if ($operation === 'Delete') {
            $invoice->update(['qbo_id' => null, 'qbo_sync_token' => null, 'qbo_synced_at' => null]);
            $this->qbo->log('invoice', $invoice->id, 'pull', 'success', $qboId, 'Invoice deleted in QBO — sync link cleared');
            return;
        }

        try {
            $response   = $this->qbo->get("invoice/{$qboId}");
            $qboInvoice = $response['Invoice'] ?? null;

            if (! $qboInvoice) {
                Log::warning("[QBO Webhook] Invoice #{$qboId} fetch returned empty response.");
                return;
            }

            $totalAmt   = (float) ($qboInvoice['TotalAmt'] ?? 0);
            $balance    = (float) ($qboInvoice['Balance'] ?? 0);
            $amountPaid = round($totalAmt - $balance, 2);

            $invoice->update([
                'qbo_sync_token' => $qboInvoice['SyncToken'],
                'qbo_synced_at'  => now(),
                'amount_paid'    => $amountPaid,
            ]);

            $invoice->refresh();

            // Derive FM status from the updated amount_paid
            app(InvoiceService::class)->derivePaymentStatus($invoice);

            $this->qbo->log('invoice', $invoice->id, 'pull', 'success', $qboId,
                "Invoice payment sync from QBO: paid={$amountPaid}, balance={$balance}");

        } catch (\Exception $e) {
            Log::error("[QBO Webhook] Failed to fetch Invoice #{$qboId}: " . $e->getMessage());
            $this->qbo->log('invoice', $invoice->id, 'pull', 'error', $qboId, $e->getMessage());
        }
    }

    private function buildInvoicePayload(Invoice $invoice, string $customerQboId, array $itemIds): array
    {
        $lines = [];

        // If any labour item has a value, the job is supply-and-install (service) — GST only on everything.
        // Otherwise it's a materials-only sale — GST+PST BC on material/freight, GST on labour.
        $hasLabour = $invoice->rooms->flatMap->items->contains(
            fn($i) => $i->item_type === 'labour' && $i->line_total > 0
        );

        foreach ($invoice->rooms as $room) {
            foreach ($room->items as $item) {
                $itemId = match ($item->item_type) {
                    'freight' => $itemIds['freight'],
                    'labour'  => $itemIds['labour'],
                    default   => $itemIds['material'],
                };

                // QBO Canada requires TaxCodeRef on every line.
                // 39 = GST/PST BC, 37 = GST only, 25 = Exempt
                $taxCodeId = match (true) {
                    $item->tax_amount <= 0 => '25',
                    $hasLabour             => '37',
                    $item->item_type === 'labour' => '37',
                    default                => '39',
                };

                $lines[] = [
                    'Amount'      => (float) $item->line_total,
                    'DetailType'  => 'SalesItemLineDetail',
                    'Description' => ($room->name ? $room->name . ' — ' : '') . $item->label,
                    'SalesItemLineDetail' => [
                        'ItemRef'    => ['value' => $itemId],
                        'Qty'        => (float) $item->quantity,
                        'UnitPrice'  => (float) $item->sell_price,
                        'TaxCodeRef' => ['value' => $taxCodeId],
                    ],
                ];
            }
        }

        $payload = [
            'CustomerRef' => ['value' => $customerQboId],
            'TxnDate'     => $invoice->created_at->toDateString(),
            'DocNumber'   => $invoice->invoice_number,
            'Line'        => $lines,
        ];

        if ($invoice->due_date) {
            $payload['DueDate'] = $invoice->due_date->toDateString();
        }

        if ($invoice->notes) {
            $payload['CustomerMemo'] = ['value' => $invoice->notes];
        }

        return $payload;
    }
}
