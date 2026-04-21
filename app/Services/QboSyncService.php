<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Vendor;

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
                    $response = $this->qbo->post('vendor', $payload);
                    $qboVendor = $response['Vendor'];
                    $action = 'created';
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
     * $apAccountId = QBO Account ID for the AP expense account (e.g. "7" for Accounts Payable)
     */
    public function pushBill(Bill $bill, string $apAccountId): array
    {
        try {
            if ($bill->bill_type !== 'vendor') {
                return ['success' => false, 'message' => 'Only vendor bills can be pushed to QBO at this time.', 'qbo_id' => null];
            }

            $bill->load(['vendor', 'items']);

            // Ensure vendor is synced to QBO first
            $vendor = $bill->vendor;
            if (! $vendor) {
                return ['success' => false, 'message' => 'Bill has no vendor assigned.', 'qbo_id' => null];
            }

            if (! $vendor->qbo_id) {
                $vendorResult = $this->pushVendor($vendor);
                if (! $vendorResult['success']) {
                    return ['success' => false, 'message' => 'Failed to sync vendor: ' . $vendorResult['message'], 'qbo_id' => null];
                }
                $vendor->refresh();
            }

            $payload = $this->buildBillPayload($bill, $vendor->qbo_id, $apAccountId);

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

    private function buildBillPayload(Bill $bill, string $vendorQboId, string $apAccountId): array
    {
        $lines = [];

        foreach ($bill->items as $item) {
            $lines[] = [
                'Amount'      => (float) $item->line_total,
                'DetailType'  => 'AccountBasedExpenseLineDetail',
                'Description' => $item->item_name . ($item->quantity ? ' (Qty: ' . $item->quantity . ' @ $' . number_format($item->unit_cost, 2) . ')' : ''),
                'AccountBasedExpenseLineDetail' => [
                    'AccountRef'     => ['value' => $apAccountId],
                    'BillableStatus' => 'NotBillable',
                ],
            ];
        }

        // GST tax line
        if ($bill->gst_amount > 0) {
            $lines[] = [
                'Amount'      => (float) $bill->gst_amount,
                'DetailType'  => 'AccountBasedExpenseLineDetail',
                'Description' => 'GST',
                'AccountBasedExpenseLineDetail' => [
                    'AccountRef' => ['value' => $apAccountId],
                    'BillableStatus' => 'NotBillable',
                ],
            ];
        }

        // PST tax line
        if ($bill->pst_amount > 0) {
            $lines[] = [
                'Amount'      => (float) $bill->pst_amount,
                'DetailType'  => 'AccountBasedExpenseLineDetail',
                'Description' => 'PST',
                'AccountBasedExpenseLineDetail' => [
                    'AccountRef' => ['value' => $apAccountId],
                    'BillableStatus' => 'NotBillable',
                ],
            ];
        }

        $payload = [
            'VendorRef'  => ['value' => $vendorQboId],
            'TxnDate'    => $bill->bill_date->toDateString(),
            'DocNumber'  => $bill->reference_number,
            'Line'       => $lines,
        ];

        if ($bill->due_date) {
            $payload['DueDate'] = $bill->due_date->toDateString();
        }

        if ($bill->notes) {
            $payload['PrivateNote'] = $bill->notes;
        }

        return $payload;
    }

    // =========================================================================
    // Invoices (AR)
    // =========================================================================

    /**
     * Push an invoice to QBO as an Invoice entity.
     * Customer (job site) is auto-synced if not already in QBO.
     * $incomeAccountId = QBO Account ID for the income/revenue account
     */
    public function pushInvoice(Invoice $invoice, string $incomeAccountId): array
    {
        try {
            $invoice->load(['rooms.items', 'sale.opportunity.jobSiteCustomer.parent', 'sale.opportunity.parentCustomer']);

            $sale        = $invoice->sale;
            $opportunity = $sale?->opportunity;
            $jobSite     = $opportunity?->jobSiteCustomer;
            $parent      = $jobSite?->parent ?? $opportunity?->parentCustomer;

            if (! $jobSite) {
                return ['success' => false, 'message' => 'Invoice has no job site customer linked.', 'qbo_id' => null];
            }

            // Ensure parent customer is synced first
            if ($parent && ! $parent->qbo_id) {
                $parentResult = $this->pushCustomer($parent);
                if (! $parentResult['success']) {
                    return ['success' => false, 'message' => 'Failed to sync parent customer: ' . $parentResult['message'], 'qbo_id' => null];
                }
                $parent->refresh();
            }

            // Ensure job site customer is synced
            if (! $jobSite->qbo_id) {
                $customerResult = $this->pushCustomer($jobSite);
                if (! $customerResult['success']) {
                    return ['success' => false, 'message' => 'Failed to sync customer: ' . $customerResult['message'], 'qbo_id' => null];
                }
                $jobSite->refresh();
            }

            $payload = $this->buildInvoicePayload($invoice, $jobSite->qbo_id, $incomeAccountId);

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

    private function buildInvoicePayload(Invoice $invoice, string $customerQboId, string $incomeAccountId): array
    {
        $lines = [];

        foreach ($invoice->rooms as $room) {
            foreach ($room->items as $item) {
                $lines[] = [
                    'Amount'      => (float) $item->line_total,
                    'DetailType'  => 'SalesItemLineDetail',
                    'Description' => ($room->name ? $room->name . ' — ' : '') . $item->label,
                    'SalesItemLineDetail' => [
                        'ItemRef'   => ['value' => 'ACCOUNT-' . $incomeAccountId, 'name' => 'Services'],
                        'Qty'       => (float) $item->quantity,
                        'UnitPrice' => (float) $item->sell_price,
                    ],
                ];
            }
        }

        // Tax line
        if ($invoice->tax_amount > 0) {
            $lines[] = [
                'Amount'      => (float) $invoice->tax_amount,
                'DetailType'  => 'SalesItemLineDetail',
                'Description' => 'Tax',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => 'ACCOUNT-' . $incomeAccountId, 'name' => 'Services'],
                ],
            ];
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
