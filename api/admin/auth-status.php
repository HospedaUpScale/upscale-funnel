<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth) {
    JsonResponse::send([
        'status' => 'unauthenticated',
        'role' => 'guest',
        'message' => 'Token de administração ausente ou inválido.'
    ], 401);
}

$role = $auth['role'];
$ws = $auth['workspace'];

$accessibleWorkspaces = [];
$canCreateSubaccounts = false;
$canViewPartners = false;
$canViewAllWorkspaces = false;
$parentPartner = null;

if ($role === 'super_admin') {
    $canCreateSubaccounts = true;
    $canViewPartners = true;
    $canViewAllWorkspaces = true;

    $all = $wsRepo->all();
    foreach ($all as $w) {
        $parentName = null;
        if ($w->parentId) {
            $p = $wsRepo->findById($w->parentId);
            $parentName = $p ? $p->name : null;
        }

        $accessibleWorkspaces[] = [
            'id'            => $w->id,
            'parent_id'     => $w->parentId,
            'parent_name'   => $parentName,
            'type'          => $w->type,
            'name'          => $w->name,
            'slug'          => $w->slug,
            'custom_domain' => $w->customDomain,
            'status'        => $w->status,
            'is_subaccount' => !empty($w->parentId),
        ];
    }
} elseif ($role === 'partner_admin') {
    $canCreateSubaccounts = true;
    $canViewPartners = true;
    $canViewAllWorkspaces = false;

    // Próprio workspace parceiro
    $accessibleWorkspaces[] = [
        'id'            => $ws->id,
        'parent_id'     => null,
        'parent_name'   => null,
        'type'          => $ws->type,
        'name'          => $ws->name,
        'slug'          => $ws->slug,
        'custom_domain' => $ws->customDomain,
        'status'        => $ws->status,
        'is_subaccount' => false,
    ];

    // Subcontas filhas do parceiro
    $descendants = $wsRepo->findDescendants($ws->id);
    foreach ($descendants as $d) {
        $accessibleWorkspaces[] = [
            'id'            => $d->id,
            'parent_id'     => $d->parentId,
            'parent_name'   => $ws->name,
            'type'          => $d->type,
            'name'          => $d->name,
            'slug'          => $d->slug,
            'custom_domain' => $d->customDomain,
            'status'        => $d->status,
            'is_subaccount' => true,
        ];
    }
} else { // merchant_admin (Subconta de Cliente / Rifa)
    $canCreateSubaccounts = false;
    $canViewPartners = false;
    $canViewAllWorkspaces = false;

    $parentName = null;
    if ($ws->parentId) {
        $p = $wsRepo->findById($ws->parentId);
        if ($p) {
            $parentName = $p->name;
            $parentPartner = [
                'id'   => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
            ];
        }
    }

    $accessibleWorkspaces[] = [
        'id'            => $ws->id,
        'parent_id'     => $ws->parentId,
        'parent_name'   => $parentName,
        'type'          => $ws->type,
        'name'          => $ws->name,
        'slug'          => $ws->slug,
        'custom_domain' => $ws->customDomain,
        'status'        => $ws->status,
        'is_subaccount' => !empty($ws->parentId),
    ];
}

JsonResponse::send([
    'status' => 'success',
    'auth' => [
        'role' => $role,
        'role_label' => match($role) {
            'super_admin'    => 'upScale Master',
            'partner_admin'  => 'Parceiro White-Label',
            'merchant_admin' => 'Subconta de Cliente (Rifa)',
            default          => 'Visitante'
        },
        'workspace' => $ws ? [
            'id'            => $ws->id,
            'parent_id'     => $ws->parentId,
            'name'          => $ws->name,
            'slug'          => $ws->slug,
            'custom_domain' => $ws->customDomain,
            'type'          => $ws->type,
        ] : null,
        'parent_partner' => $parentPartner,
        'permissions' => [
            'can_create_subaccounts' => $canCreateSubaccounts,
            'can_view_partners'      => $canViewPartners,
            'can_view_all_workspaces'=> $canViewAllWorkspaces,
        ],
        'accessible_workspaces' => $accessibleWorkspaces,
    ]
]);
