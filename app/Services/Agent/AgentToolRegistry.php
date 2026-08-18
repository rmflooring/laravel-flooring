<?php

namespace App\Services\Agent;

/**
 * JSON tool-schema definitions handed to the Claude Messages API. Modules 1-5 wire up
 * attach_images, attach_document, find_opportunity, update_opportunity,
 * create_opportunity, log_communication, check_status, plus the two meta-tools every
 * task needs to be able to terminate sanely (request_clarification, no_actionable_intent).
 * `undo_last_action` is the only v1 tool not yet built.
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
                    . 'mentioned — client name, job site address, insurance claim number, and/or a job/reference number '
                    . '(RM Flooring\'s own, or a referring company\'s — e.g. "job # 00705807" in a subject line or body). '
                    . 'Call this whenever no opportunity is already resolved for this task and the email appears to '
                    . 'reference an existing job. Returns scored candidates; if a single unambiguous high-confidence '
                    . 'match is found it is automatically resolved for the task. If the result is ambiguous or empty, '
                    . 'use request_clarification rather than guessing.',
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
                        'job_no' => [
                            'type' => 'string',
                            'description' => 'A job/work-order/reference number mentioned in the email, if any — '
                                . 'RM Flooring\'s own or a referring company\'s. Matched exactly against the opportunity\'s '
                                . 'job number, whatever format it\'s in.',
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
                            'description' => 'Name of an existing parent company to link this job under, exactly as it '
                                . 'should match an existing customer record. This is very often the company that sent or '
                                . 'forwarded the referral itself — a restoration company, property manager, or similar '
                                . '(check the sender\'s organization/domain and email signature, not just who the job is '
                                . 'for) — not only a property manager. Omit entirely if client_name is the only party '
                                . 'involved, or if you\'re not confident of an exact match — a new standalone customer '
                                . 'record will be created and used as both parent and job site, and this can be '
                                . 'corrected later. Never guess at a close-but-not-exact name; if unsure, omit it rather '
                                . 'than risk linking to the wrong company.',
                        ],
                        'address' => [
                            'type' => 'string',
                            'description' => 'Job site address mentioned in the email, if any.',
                        ],
                        'claim_number' => [
                            'type' => 'string',
                            'description' => 'Insurance claim number mentioned in the email, if any.',
                        ],
                        'job_no' => [
                            'type' => 'string',
                            'description' => 'A job/work-order/reference number the referring company (restoration '
                                . 'company, insurer, property manager, etc.) uses for this job, if one is mentioned — '
                                . 'e.g. in the subject line ("Job #00705807") or body. Pass it through as-is, whatever '
                                . 'format it\'s in (a numeric reference, a dated code, etc.) — this becomes the '
                                . 'opportunity\'s job number, matching how these are normally recorded. Omit if no '
                                . 'reference number is mentioned anywhere; leaving it blank for staff to assign is fine.',
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
                'name' => 'log_communication',
                'description' => 'Record a summary of an email/correspondence thread onto the resolved opportunity\'s '
                    . 'activity log. Use this when the email is clearly about the opportunity already resolved for this '
                    . 'task, contains information worth preserving (a client update, an adjuster call, a vendor note, '
                    . 'etc.), and no other tool (attach_images, attach_document, update_opportunity) already covers it.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'opportunity_id' => [
                            'type' => 'integer',
                            'description' => 'The opportunity ID already resolved for this task. Must match exactly.',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'A concise summary of the correspondence to record on the activity log.',
                        ],
                        'from' => [
                            'type' => 'string',
                            'description' => 'Who the correspondence was from, if identifiable (e.g. a client or adjuster '
                                . 'name) — not necessarily the same as the email\'s From header, which is whoever forwarded it.',
                        ],
                        'category' => [
                            'type' => 'string',
                            'enum' => LogCommunicationService::CATEGORIES,
                            'description' => 'What kind of communication this is.',
                        ],
                    ],
                    'required' => ['opportunity_id', 'summary', 'category'],
                ],
            ],
            [
                'name' => 'check_status',
                'description' => 'Read-only lookup of the resolved opportunity\'s current status — job status, RFM '
                    . '(site measure) status/date, latest estimate status, latest sale status, and assigned project '
                    . 'manager. Use this to answer a status-inquiry email; it concludes the task with the summary as the reply.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'opportunity_id' => [
                            'type' => 'integer',
                            'description' => 'The opportunity ID already resolved for this task. Must match exactly.',
                        ],
                    ],
                    'required' => ['opportunity_id'],
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

    /**
     * Tool schemas for the staff chat knowledge agent. All read-only. Each tool's
     * execution service re-checks the calling user's role server-side — Claude
     * choosing to call a tool here is never itself authorization.
     */
    public static function forChat(): array
    {
        return [
            [
                'name' => 'search_knowledge_base',
                'description' => 'Search the internal knowledge base (pricing, protocols, policies, SOPs, FAQs) '
                    . 'for information relevant to the staff member\'s question. Returns the best-matching '
                    . 'excerpts, each with the knowledge entry it came from, so the answer can cite its source. '
                    . 'Only returns entries the requesting user\'s role is allowed to see.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The question or topic to search for.',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'get_work_order_status',
                'description' => 'Look up a work order\'s current status, schedule, and assigned installer by '
                    . 'its WO number or ID.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_id' => [
                            'type' => 'string',
                            'description' => 'The work order number (e.g. "412-26-0001") or numeric ID.',
                        ],
                    ],
                    'required' => ['order_id'],
                ],
            ],
            [
                'name' => 'check_inventory',
                'description' => 'Look up how much of a product is currently available in inventory by its SKU.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'sku' => [
                            'type' => 'string',
                            'description' => 'The product SKU to look up.',
                        ],
                    ],
                    'required' => ['sku'],
                ],
            ],
            [
                'name' => 'get_customer_estimate',
                'description' => 'Look up a customer estimate\'s status, job info, and total by its estimate '
                    . 'number or ID. No line-item pricing breakdown — summary only.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estimate_id' => [
                            'type' => 'string',
                            'description' => 'The estimate number or numeric ID.',
                        ],
                    ],
                    'required' => ['estimate_id'],
                ],
            ],
            [
                'name' => 'get_schedule_for_crew',
                'description' => 'Look up what an install crew is scheduled to work on for a given date. '
                    . '"Crew" refers to an installer company/person in Floor Manager.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'crew_id' => [
                            'type' => 'string',
                            'description' => 'The installer\'s name or numeric ID.',
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'The date to check, in any recognizable format.',
                        ],
                    ],
                    'required' => ['crew_id', 'date'],
                ],
            ],
        ];
    }
}
