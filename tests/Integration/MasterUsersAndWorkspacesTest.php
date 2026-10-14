<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use PDO;

class MasterUsersAndWorkspacesTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private WorkspaceRepository $workspaceRepo;

    protected function setUp(): void
    {
        $this->pdo = Connection::make();
        $this->userRepo = new UserRepository($this->pdo);
        $this->workspaceRepo = new WorkspaceRepository($this->pdo);
    }

    public function testMasterCanListAllUsersWithWorkspacesAndRoles(): void
    {
        $users = $this->userRepo->listAll();

        $this->assertTrue(!empty($users), "listAll must return at least one user from seed or system.");
        
        // Verifica a estrutura dos usuários retornados
        $firstUser = $users[0];
        $this->assertArrayHasKey('id', $firstUser);
        $this->assertArrayHasKey('email', $firstUser);
        $this->assertArrayHasKey('name', $firstUser);
        $this->assertArrayHasKey('workspaces', $firstUser);
        $this->assertTrue(is_array($firstUser['workspaces']), "Workspaces field must be an array of memberships.");

        // O password_hash não deve estar exposto
        $this->assertFalse(isset($firstUser['password_hash']), "password_hash must be protected and omitted.");
    }

    public function testMasterCanCreateGlobalUserWithSpecificWorkspaces(): void
    {
        $email = 'cliente.teste.' . time() . '@rifas.com';
        $name = 'Cliente Lojista Teste';
        $password = 'SenhaForte123!';
        $phone = '(11) 98888-7777';

        // Workspaces para associar
        $workspaces = $this->workspaceRepo->all();
        $this->assertTrue(!empty($workspaces), "Must have workspaces in system.");
        $targetWs = $workspaces[0];

        $memberships = [
            [
                'workspace_id' => $targetWs->id,
                'role' => 'merchant_admin'
            ]
        ];

        $createdUser = $this->userRepo->createGlobalUser($name, $email, $password, $phone, $memberships);

        $this->assertTrue(!empty($createdUser), "User should be created.");
        $this->assertEquals($email, $createdUser['email']);
        $this->assertEquals($name, $createdUser['name']);
        $this->assertEquals($phone, $createdUser['phone']);
        $this->assertTrue($createdUser['has_password']);

        // Verifica na listagem geral se aparece com o workspace vinculado
        $allUsers = $this->userRepo->listAll();
        $found = null;
        foreach ($allUsers as $u) {
            if ($u['email'] === $email) {
                $found = $u;
                break;
            }
        }

        $this->assertTrue($found !== null, "Created user must be found in listAll().");
        $this->assertEquals(1, count($found['workspaces']));
        $this->assertEquals($targetWs->id, $found['workspaces'][0]['workspace_id']);
        $this->assertEquals('merchant_admin', $found['workspaces'][0]['role']);

        // Limpeza
        $this->userRepo->deleteGlobalUser((int)$createdUser['id']);
    }

    public function testMasterCanCreateWorkspacesForOtherClientsWithAutoSlugAndInitialUser(): void
    {
        $wsName = 'Cliente Rifa Automatica ' . rand(100, 999);
        $clientEmail = 'dono.' . time() . '@rifaauto.com';
        $clientPass = 'ChaveSecreta99';

        // 1. Criação com WorkspaceRepository::create
        $createdWs = $this->workspaceRepo->create([
            'name' => $wsName,
            'slug' => 'cliente-rifa-' . time(),
            'type' => 'merchant',
            'api_token_hash' => hash('sha256', 'api_token_' . time()),
            'admin_token_hash' => hash('sha256', 'admin_token_' . time()),
            'gateway_provider' => 'mock',
            'status' => 'active'
        ]);

        $this->assertTrue($createdWs !== null, "Workspace must be created.");
        $this->assertStringContains('cliente-rifa', $createdWs->slug);
        $this->assertEquals('merchant', $createdWs->type);

        // 2. Criar usuário de acesso inicial associado a esse novo workspace
        $initialUser = $this->userRepo->createGlobalUser(
            name: $wsName . ' Admin',
            email: $clientEmail,
            password: $clientPass,
            phone: null,
            memberships: [
                ['workspace_id' => $createdWs->id, 'role' => 'merchant_admin']
            ]
        );

        $this->assertTrue(!empty($initialUser));
        $this->assertEquals($clientEmail, $initialUser['email']);

        // 3. Verificar que o usuário consegue autenticar no workspace
        $authFound = $this->userRepo->findByEmailAndWorkspace($clientEmail, (int)$createdWs->id);
        $this->assertTrue($authFound !== null, "User must be found for the newly created workspace.");
        $this->assertEquals('merchant_admin', $authFound['role']);

        // Limpeza
        $this->userRepo->deleteGlobalUser((int)$initialUser['id']);
        $this->pdo->prepare("DELETE FROM workspaces WHERE id = ?")->execute([$createdWs->id]);
    }

    public function testMasterCanUpdateAndDeleteGlobalUser(): void
    {
        $email = 'temporario.' . time() . '@upscale.com';
        $user = $this->userRepo->createGlobalUser('Nome Original', $email, 'senha123', '1111-2222', []);
        $userId = (int)$user['id'];

        // Atualizar nome, telefone e promover a super_admin
        $success = $this->userRepo->updateGlobalUser($userId, [
            'name' => 'Nome Atualizado',
            'phone' => '9999-8888',
            'memberships' => [
                ['workspace_id' => 1, 'role' => 'super_admin']
            ]
        ]);
        $this->assertTrue($success, "updateGlobalUser should return true on success.");

        // Obter usuário da listagem e checar atualização
        $all = $this->userRepo->listAll();
        $updatedUser = null;
        foreach ($all as $u) {
            if ($u['id'] === $userId) {
                $updatedUser = $u;
                break;
            }
        }
        $this->assertTrue($updatedUser !== null, "User should still exist.");
        $this->assertEquals('Nome Atualizado', $updatedUser['name']);
        $this->assertEquals('9999-8888', $updatedUser['phone']);

        // Deletar usuário
        $deleted = $this->userRepo->deleteGlobalUser($userId);
        $this->assertTrue($deleted);

        // Confirmar que não existe mais
        $allAfter = $this->userRepo->listAll();
        $exists = array_filter($allAfter, fn($u) => $u['id'] === $userId);
        $this->assertTrue(empty($exists), "User must not exist after deletion.");
    }
}
