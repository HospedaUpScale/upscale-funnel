/**
 * Camada de salvamento/publicação do editor visual.
 * Fala com /api/admin/visual-editor.php (ações load, save-draft, publish).
 *
 * - Debounce de salvamento automático (3s após alteração).
 * - Trava concorrência: enquanto um save está em voo, novos saves aguardam.
 * - Controle de revisão (expected_revision ↔ servidor).
 * - Avisa ao sair com alterações pendentes.
 */
(function (window) {
  'use strict';

  var API = '../api/admin/visual-editor.php';
  var SAVE_DEBOUNCE_MS = 3000;

  function create(opts) {
    var editor = opts.editor;
    var state = {
      id: opts.id || 0,
      workspaceId: opts.workspaceId || 1,
      revision: 0,
      name: 'Nova página',
      slug: '',
      saving: false,
      queued: false,
      dirty: false,
      lastSavedAt: null,
      token: opts.token || '',
      onStatus: opts.onStatus || function () {},
    };

    function headers() {
      var h = { 'Content-Type': 'application/json' };
      if (state.token) {
        h['Authorization'] = 'Bearer ' + state.token;
        h['X-Admin-Token'] = state.token;
      }
      return h;
    }

    async function load(id) {
      state.onStatus({ phase: 'loading' });
      var r = await fetch(API + '?action=load&workspace_id=' + state.workspaceId, {
        method: 'POST', headers: headers(),
        body: JSON.stringify({ action: 'load', id: id || 0, workspace_id: state.workspaceId }),
      });
      var data = await r.json();
      if (!r.ok || data.status !== 'success') throw new Error(data.message || ('HTTP ' + r.status));
      state.id = data.id || 0;
      state.revision = data.revision || 0;
      state.name = data.name || 'Nova página';
      state.slug = data.slug || '';
      // Carrega project data no editor
      if (data.project_data) {
        editor.loadProjectData(data.project_data);
      }
      state.dirty = false;
      state.onStatus({ phase: 'loaded', data: data });
      return data;
    }

    function markDirty() {
      state.dirty = true;
      state.onStatus({ phase: 'dirty' });
      scheduleSave();
    }

    var saveTimer = null;
    function scheduleSave() {
      if (saveTimer) clearTimeout(saveTimer);
      saveTimer = setTimeout(saveDraft, SAVE_DEBOUNCE_MS);
    }

    async function saveDraft() {
      if (state.saving) { state.queued = true; return; }
      state.saving = true;
      state.onStatus({ phase: 'saving' });

      try {
        var projectData = editor.getProjectData();
        var html = editor.getHtml();
        var css = editor.getCss();

        var r = await fetch(API + '?action=save-draft&workspace_id=' + state.workspaceId, {
          method: 'POST', headers: headers(),
          body: JSON.stringify({
            action: 'save-draft',
            id: state.id,
            workspace_id: state.workspaceId,
            name: state.name,
            slug: state.slug || state.name,
            project_data: projectData,
            html: wrapPublishedBody(html, css),
            css: css,
            expected_revision: state.revision,
          }),
        });
        var data = await r.json();
        if (r.status === 409 && data.code === 'revision_conflict') {
          state.onStatus({ phase: 'conflict', current_revision: data.current_revision });
          return;
        }
        if (!r.ok || data.status !== 'success') throw new Error(data.message || ('HTTP ' + r.status));
        state.id = data.id;
        state.slug = data.slug;
        state.revision = data.revision;
        state.dirty = false;
        state.lastSavedAt = data.saved_at;
        state.onStatus({ phase: 'saved', data: data });
      } catch (e) {
        state.onStatus({ phase: 'error', message: String(e.message || e) });
      } finally {
        state.saving = false;
        if (state.queued) { state.queued = false; scheduleSave(); }
      }
    }

    async function publish(opts2) {
      // Garante que o rascunho atual esteja no servidor antes de publicar.
      if (state.dirty || !state.id) {
        await saveDraft();
      }
      state.onStatus({ phase: 'publishing' });
      var r = await fetch(API + '?action=publish&workspace_id=' + state.workspaceId, {
        method: 'POST', headers: headers(),
        body: JSON.stringify({
          action: 'publish',
          id: state.id,
          workspace_id: state.workspaceId,
          expected_revision: state.revision,
          title: (opts2 && opts2.title) || state.name,
          description: (opts2 && opts2.description) || '',
        }),
      });
      var data = await r.json();
      if (r.status === 409 && data.code === 'revision_conflict') {
        state.onStatus({ phase: 'conflict', current_revision: data.current_revision });
        return null;
      }
      if (!r.ok || data.status !== 'success') throw new Error(data.message || ('HTTP ' + r.status));
      state.revision = data.revision;
      state.onStatus({ phase: 'published', data: data });
      return data;
    }

    // Monta o body publicado incluindo o CSS do GrapesJS junto com o do template.
    // O endpoint de publish aceita html pronto, mas o save-draft precisa de HTML
    // standalone para também servir como preview rápido. Aqui concatenamos:
    function wrapPublishedBody(html, css) {
      var runtime = (window.veComponents && window.veComponents.publicRuntime) || '';
      return '<style>' + css + '</style>\n' + html + '\n' + runtime;
    }

    // Aviso ao sair se tem alterações pendentes
    window.addEventListener('beforeunload', function (ev) {
      if (state.dirty) {
        ev.preventDefault();
        ev.returnValue = 'Você tem alterações não salvas.';
        return ev.returnValue;
      }
    });

    return {
      load: load,
      saveDraft: saveDraft,
      publish: publish,
      markDirty: markDirty,
      state: state,
      setName: function (n) { state.name = n; markDirty(); },
      setSlug: function (s) { state.slug = s; markDirty(); },
    };
  }

  window.veStorage = { create: create };
})(window);
