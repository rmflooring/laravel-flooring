<?php

namespace App\Services\Agent;

/**
 * JSON tool-schema definitions handed to the Claude Messages API. Module 1
 * wires up attach_images plus the two meta-tools every task needs to be able
 * to terminate sanely (request_clarification, no_actionable_intent) — the
 * rest of the v1 tool library (find_opportunity, create_opportunity, etc.)
 * lands in later modules.
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
