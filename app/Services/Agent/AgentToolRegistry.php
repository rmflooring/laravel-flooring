<?php

namespace App\Services\Agent;

/**
 * JSON tool-schema definitions handed to the Claude Messages API. Modules 1-4 wire up
 * attach_images, attach_document, find_opportunity, update_opportunity,
 * create_opportunity, plus the two meta-tools every task needs to be able to terminate
 * sanely (request_clarification, no_actionable_intent) — the rest of the v1 tool library
 * (log_communication, check_status, undo_last_action) lands in later modules.
 */
class AgentToolRegistry
{
    public static function forEmail(): array
    {
        return [
            [
                'name' => 'attach_images',
                'description' => 'Attach one or more email image attachments to the resolved opportunity\'s photo gallery. '
                    . 'Only call this when the email is about the opportunity already resolved for this task and contains image attachments.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'opportunity_id' => [
                            'type' => 'integer',
                            'description' => 'The opportunity ID already resolved for this task. Must match exactly.',
                        ],
                        'attachment_indices' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => 'Indices (0-based) of the email\'s image attachments to attach. '
                                . 'Omit or pass an empty array to attach every image attachment on the email.',
                        ],
                        'category' => [
                            'type' => 'string',
                            'enum' => AttachImagesService::CATEGORIES,
                            'description' => 'What the photo(s) depict.',
                        ],
                        'label' => [
                            'type' => 'string',
                            'description' => 'Optional short freetext note about the photo(s), e.g. "kitchen subfloor".',
                        ],
                    ],
                    'required' => ['opportunity_id', 'category'],
                ],
            ],
            [
                'name' => 'attach_document',
                'description' => 'Attach a single email document attachment (PDF, Word doc, or scanned image) to the resolved '
                    . 'opportunity\'s documents. Only call this when the email is about the opportunity already resolved for '
                    . 'this task and contains a document attachment such as a scope of work, contract, or insurance certificate.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'opportunity_id' => [
                            'type' => 'integer',
                            'description' => 'The opportunity ID already resolved for this task. Must match exactly.',
                        ],
                        'attachment_index' => [
                            'type' => 'integer',
                            'description' => 'Index (0-based) of the email\'s document attachment to attach.',
                        ],
                        'document_type' => [
                            'type' => 'string',
                            'enum' => AttachDocumentService::DOCUMENT_TYPES,
                            'description' => 'What kind of document this is.',
                        ],
                        'label' => [
                            'type' => 'string',
                            'description' => 'Optional short freetext note about the document, e.g. "signed by homeowner".',
                        ],
                    ],
                    'required' => ['opportunity_id', 'attachment_index', 'document_type'],
                ],
            ],
            [
                'name' => 'find_opportunity',
                'description' => 'Search for the opportunity an email relates to, using whatever identifying details are '
                    . 'mentioned — client name, job site address, and/or insurance claim number. Call this whenever no '
                    . 'opportunity is already resolved for this task and the email appears to reference an existing job. '
                    . 'Returns scored candidates; if a single unambiguous high-confidence match is found it is '
                    . 'automatically resolved for the task. If the result is ambiguous or empty, use request_clarification '
                    . 'rather than guessing.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_name' => [
                            'type' => 'string',
                            'description' => 'Name or company name of the customer/client mentioned in the email, if any.',
                        ],
                        'address' => [
                            'type' => 'string',
                            'description' => 'Job site address mentioned in the email, if any.',
                        ],
                        'claim_number' => [
                            'type' => 'string',
                            'description' => 'Insurance claim number mentioned in the email, if any.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'update_opportunity',
                'description' => 'Update the resolved opportunity. Only two fields are supported in this version: '
                    . 'whether it requires an RFM (site measure) visit, and assigning a project manager by name. Any '
                    . 'other requested change (status, job number, sales person, customer details, etc.) is out of '
                    . 'scope — use request_clarification or no_actionable_intent instead of attempting it.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'opportunity_id' => [
                            'type' => 'integer',
                            'description' => 'The opportunity ID already resolved for this task. Must match exactly.',
                        ],
                        'requires_rfm' => [
                            'type' => 'boolean',
                            'description' => 'Whether this opportunity requires an RFM (site measure) visit.',
                        ],
                        'project_manager_name' => [
                            'type' => 'string',
                            'description' => 'Name of the project manager to assign, exactly as it should match an '
                                . 'existing project manager record for this opportunity\'s customer.',
                        ],
                    ],
                    'required' => ['opportunity_id'],
                ],
            ],
            [
                'name' => 'create_opportunity',
                'description' => 'Create a brand new opportunity for a job that does not exist in Floor Manager yet '
                    . '(e.g. a new insurance referral or a new lead). Only call this after find_opportunity has already '
                    . 'been tried and found nothing (or only low-confidence matches) — never call it when an '
                    . 'opportunity is already resolved for this task. A duplicate check runs automatically; if a '
                    . 'similar recent opportunity is found, this will fail and you should use request_clarification '
                    . 'instead of retrying.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_name' => [
                            'type' => 'string',
                            'description' => 'Name of the job-site contact / homeowner / claimant. The only required field.',
                        ],
                        'parent_customer_name' => [
                            'type' => 'string',
                            'description' => 'Name of an existing parent company (e.g. a property manager) to link this '
                                . 'job under, exactly as it should match an existing customer record. Omit entirely if '
                                . 'client_name is the only party involved — a new standalone customer record will be '
                                . 'created and used as both parent and job site.',
                        ],
                        'address' => [
                            'type' => 'string',
                            'description' => 'Job site address mentioned in the email, if any.',
                        ],
                        'claim_number' => [
                            'type' => 'string',
                            'description' => 'Insurance claim number mentioned in the email, if any.',
                        ],
                        'insurance_company' => [
                            'type' => 'string',
                            'description' => 'Insurance company mentioned in the email, if any.',
                        ],
                        'adjuster' => [
                            'type' => 'string',
                            'description' => 'Insurance adjuster\'s name mentioned in the email, if any.',
                        ],
                        'policy_number' => [
                            'type' => 'string',
                            'description' => 'Insurance policy number mentioned in the email, if any.',
                        ],
                        'dol' => [
                            'type' => 'string',
                            'description' => 'Date of loss mentioned in the email, if any (any recognizable date format).',
                        ],
                        'requires_rfm' => [
                            'type' => 'boolean',
                            'description' => 'Whether this opportunity requires an RFM (site measure) visit. Defaults to '
                                . 'true (a new opportunity almost always needs one) if omitted.',
                        ],
                    ],
                    'required' => ['client_name'],
                ],
            ],
            [
                'name' => 'request_clarification',
                'description' => 'Use this when you cannot confidently determine which opportunity the email relates to, '
                    . 'or the request is ambiguous in some other way. Writes a question for a staff member to answer in the dashboard.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => [
                            'type' => 'string',
                            'description' => 'The specific question to ask the requester or a staff reviewer.',
                        ],
                    ],
                    'required' => ['question'],
                ],
            ],
            [
                'name' => 'no_actionable_intent',
                'description' => 'Use this when the email is not an actionable request at all (spam, a newsletter, '
                    . 'an unrelated forward, etc.) and no other tool applies.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ],
        ];
    }
}
