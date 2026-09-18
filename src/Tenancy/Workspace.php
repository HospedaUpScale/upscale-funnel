<?php

namespace App\Tenancy;

class Workspace
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $type,
        public string $name,
        public string $slug,
        public ?string $customDomain,
        public ?string $document,
        public string $apiTokenHash,
        public string $adminTokenHash,
        public string $gatewayProvider,
        public ?array $gatewayCredentials,
        public ?string $splitRecipientId,
        public string $status
    ) {}

    public static function fromRow(array $row): self
    {
        $creds = null;
        if (!empty($row['gateway_credentials'])) {
            $creds = is_array($row['gateway_credentials'])
                ? $row['gateway_credentials']
                : json_decode($row['gateway_credentials'], true);
        }

        return new self(
            id: (int)$row['id'],
            parentId: $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
            type: $row['type'],
            name: $row['name'],
            slug: $row['slug'],
            customDomain: $row['custom_domain'] ?? null,
            document: $row['document'] ?? null,
            apiTokenHash: $row['api_token_hash'],
            adminTokenHash: $row['admin_token_hash'],
            gatewayProvider: $row['gateway_provider'] ?? 'mock',
            gatewayCredentials: $creds,
            splitRecipientId: $row['split_recipient_id'] ?? null,
            status: $row['status'] ?? 'active'
        );
    }
}
