/**
 * Inicialização do editor visual.
 * Lê workspace_id e id do funnel na URL (?id=N&workspace_id=M). Se id=0 ou
 * ausente, carrega o modelo padrão.
 */
(function () {
  'use strict';

  function qs(name) {
    try { return new URLSearchParams(location.search).get(name) || ''; } catch (e) { return ''; }
  }

  function getToken() {
    try { return localStorage.getItem('upscale_admin_token') || ''; } catch (e) { return ''; }
  }

  function toast(msg, kind) {
    var el = document.getElementById('ve-toast');
    if (!el) return;
    el.textContent = msg;
    el.className = 've-toast on ' + (kind || '');
    setTimeout(function () { el.className = 've-toast'; }, 2800);
  }

  function setStatus(txt, cls) {
    var el = document.getElementById('ve-status');
    if (!el) return;
    el.textContent = txt;
    el.className = 'status ' + (cls || '');
  }

  window.addEventListener('DOMContentLoaded', function () {
    if (!window.grapesjs) {
      document.getElementById('gjs').innerHTML =
        '<div style="padding:30px;color:#fecaca;">GrapesJS não está disponível. Execute <code>./scripts/vendor_grapesjs.sh</code> no servidor ou aguarde o próximo deploy.</div>';
      setStatus('Editor indisponível', 'err');
      return;
    }

    var editor = grapesjs.init({
      container: '#gjs',
      height: '100%',
      width: '100%',
      storageManager: false,     // persistência via nosso endpoint; nada em localStorage
      panels: { defaults: [] },  // vamos usar a UI padrão do GrapesJS (sidebars) + nossa topbar
      deviceManager: {
        devices: [
          { name: 'Desktop', width: '' },
          { name: 'Tablet',  width: '768px', widthMedia: '992px' },
          { name: 'Celular', width: '390px', widthMedia: '640px' },
        ],
      },
      canvas: {
        // Carrega o mesmo CSS do template dentro do canvas para a prévia bater
        // com a página pública. O CSS vem embutido no HTML publicado também.
        styles: [],
      },
      assetManager: {
        // Reaproveita o endpoint de upload existente (api/admin/upload.php)
        upload: '../api/admin/upload.php?workspace_id=' + encodeURIComponent(qs('workspace_id') || '1'),
        uploadName: 'file',
        headers: (function () {
          var t = getToken();
          var h = {};
          if (t) { h['Authorization'] = 'Bearer ' + t; h['X-Admin-Token'] = t; }
          return h;
        })(),
        multiUpload: false,
        autoAdd: true,
      },
    });

    // Registra componentes e blocos ANTES de qualquer loadProjectData.
    if (window.veComponents) window.veComponents.register(editor);
    if (window.veBlocks)     window.veBlocks.register(editor);

    var storage = veStorage.create({
      editor: editor,
      id: parseInt(qs('id') || '0', 10),
      workspaceId: parseInt(qs('workspace_id') || '1', 10),
      token: getToken(),
      onStatus: function (ev) {
        switch (ev.phase) {
          case 'loading':    setStatus('Carregando…', ''); break;
          case 'loaded':
            document.getElementById('ve-page-name').value = ev.data.name;
            setStatus(ev.data.id ? ('Carregado — rev ' + ev.data.revision) : 'Modelo em branco carregado', 'ok');
            break;
          case 'dirty':      setStatus('Alterações não salvas…', 'dirty'); break;
          case 'saving':     setStatus('Salvando rascunho…', 'saving'); break;
          case 'saved':      setStatus('Salvo (rev ' + ev.data.revision + ')', 'ok'); break;
          case 'publishing': setStatus('Publicando…', 'saving'); break;
          case 'published':  setStatus('Publicado (rev ' + ev.data.revision + ')', 'ok'); toast('Página publicada em /' + ev.data.slug, 'ok'); break;
          case 'conflict':   setStatus('Conflito: outra aba atualizou', 'err'); toast('A página foi alterada em outra aba. Recarregue.', 'err'); break;
          case 'error':      setStatus('Erro: ' + ev.message, 'err'); toast(ev.message, 'err'); break;
        }
      },
    });

    // Marca sujo em qualquer mudança
    editor.on('update',               storage.markDirty);
    editor.on('component:add',        storage.markDirty);
    editor.on('component:remove',     storage.markDirty);
    editor.on('component:update',     storage.markDirty);
    editor.on('style:update',         storage.markDirty);

    // Botões
    document.getElementById('ve-btn-save').addEventListener('click', function () { storage.saveDraft(); });
    document.getElementById('ve-btn-publish').addEventListener('click', function () {
      storage.publish({ title: document.getElementById('ve-page-name').value || 'Página' })
        .then(function (res) {
          if (res && res.public_url) {
            if (confirm('Página publicada em ' + res.public_url + '. Abrir em nova aba?')) {
              window.open(res.public_url, '_blank', 'noopener');
            }
          }
        })
        .catch(function (e) { toast(String(e.message || e), 'err'); });
    });
    document.getElementById('ve-btn-preview').addEventListener('click', function () {
      editor.runCommand('preview');
    });
    document.getElementById('ve-page-name').addEventListener('input', function (ev) {
      storage.setName(ev.target.value);
    });

    // Load inicial
    storage.load(parseInt(qs('id') || '0', 10)).catch(function (e) {
      setStatus('Falha ao carregar', 'err');
      toast(String(e.message || e), 'err');
    });
  });
})();
