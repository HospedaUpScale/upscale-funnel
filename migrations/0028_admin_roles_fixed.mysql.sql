-- Migration 0028: regra fixa de papéis
-- 1) Todo vínculo no workspace administrativo (tipo super_admin) vira Super admin.
UPDATE workspace_memberships SET role = 'super_admin'
WHERE role <> 'super_admin' AND workspace_id IN (SELECT id FROM workspaces WHERE type = 'super_admin');

-- 2) Quem estava marcado como Super admin dentro de um workspace de cliente passa a ser
--    Super admin pelo workspace administrativo (o vínculo de cliente com esse papel é removido).
INSERT INTO workspace_memberships (workspace_id, user_id, role)
SELECT a.id, x.user_id, 'super_admin'
FROM (SELECT DISTINCT m.user_id FROM workspace_memberships m
      INNER JOIN workspaces w ON w.id = m.workspace_id
      WHERE m.role = 'super_admin' AND w.type <> 'super_admin') x
INNER JOIN (SELECT MIN(id) AS id FROM workspaces WHERE type = 'super_admin') a ON a.id IS NOT NULL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT workspace_id, user_id FROM workspace_memberships) e WHERE e.workspace_id = a.id AND e.user_id = x.user_id);

DELETE FROM workspace_memberships
WHERE role = 'super_admin'
  AND workspace_id IN (SELECT id FROM workspaces WHERE type <> 'super_admin')
  AND EXISTS (SELECT 1 FROM workspaces WHERE type = 'super_admin');
