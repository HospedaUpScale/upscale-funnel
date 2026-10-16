(function () {
  "use strict";
  var $ = function (id) {
    return document.getElementById(id);
  };
  var params = new URLSearchParams(location.search),
    editor,
    storage,
    campaignContext = null,
    flow,
    active = 0,
    loading = false,
    mode = "visual";
  function toast(msg) {
    $("ve-toast").textContent = msg;
    $("ve-toast").className = "on";
    setTimeout(function () {
      $("ve-toast").className = "";
    }, 4500);
  }
  function token() {
    try {
      return localStorage.getItem("upscale_admin_token") || "";
    } catch (e) {
      return "";
    }
  }
  function dirty() {
    storage.markDirty();
  }
  function editorChanged() {
    if (loading || !flow || mode !== "visual") return;
    var step = flow.steps[active];
    if (
      step.html !== editor.getHtml() ||
      step.css !== editor.getCss() ||
      JSON.stringify(step.project) !== JSON.stringify(editor.getProjectData())
    )
      dirty();
  }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[c];
    });
  }
  function capture() {
    if (!flow) return;
    var step = flow.steps[active];
    if (mode === "html") {
      step.html = $("ve-html").value;
      step.raw = true;
    } else if (editor) {
      step.project = editor.getProjectData();
      step.html = editor.getHtml();
      step.css = editor.getCss();
      step.raw = false;
    }
  }
  function renderSteps() {
    var list = $("ve-steps");
    list.replaceChildren();
    flow.steps.forEach(function (step, i) {
      var b = document.createElement("button");
      b.textContent = i + 1 + ". " + step.name;
      b.className = i === active ? "active" : "";
      b.onclick = function () {
        capture();
        active = i;
        openStep();
      };
      list.appendChild(b);
    });
    $("ve-step-name").value = flow.steps[active].name;
    $("ve-delete-step").disabled = flow.steps.length === 1;
    $("ve-step-up").disabled = active === 0;
    $("ve-step-down").disabled = active === flow.steps.length - 1;
  }
  function setMode(next) {
    mode = next;
    $("gjs").hidden = mode === "html";
    $("ve-code").hidden = mode !== "html";
    $("ve-mode-visual").classList.toggle("active", mode === "visual");
    $("ve-mode-html").classList.toggle("active", mode === "html");
  }
  function openStep() {
    loading = true;
    var step = flow.steps[active];
    renderSteps();
    if (step.raw || !editor) {
      $("ve-html").value = step.html;
      setMode("html");
    } else {
      setMode("visual");
      editor.UndoManager.clear();
      if (step.project) editor.loadProjectData(step.project);
      else {
        editor.setComponents(step.html);
        editor.setStyle(step.css || "");
      }
      editor.select(null);
      editor.UndoManager.clear();
      step.html = editor.getHtml();
      step.css = editor.getCss();
      step.project = editor.getProjectData();
    }
    loading = false;
  }
  function fullHtml(step) {
    if (!step.raw) return "<style>" + step.css + "</style>" + step.html;
    return step.html;
  }
  function documentHtml(preview) {
    capture();
    if (
      flow.steps.length === 1 &&
      flow.steps[0].raw &&
      /<html[\s>]/i.test(flow.steps[0].html)
    )
      return flow.steps[0].html;
    var content = flow.steps
      .map(function (step, i) {
        var html = fullHtml(step);
        if (step.raw && /<html[\s>]/i.test(html)) {
          var doc = new DOMParser().parseFromString(html, "text/html");
          html =
            Array.from(doc.head.querySelectorAll("style,link,script"))
              .map(function (el) {
                return el.outerHTML;
              })
              .join("") + doc.body.innerHTML;
        }
        if (
          campaignContext &&
          !preview &&
          html.indexOf("data-campaign-offer") !== -1
        ) {
          var stageDoc = new DOMParser().parseFromString(html, "text/html");
          stageDoc
            .querySelectorAll("iframe[data-campaign-offer]")
            .forEach(function (frame) {
              frame.removeAttribute("srcdoc");
              frame.setAttribute("src", "{{campaign_offer_url}}");
            });
          html = stageDoc.head.innerHTML + stageDoc.body.innerHTML;
        }
        return (
          '<section data-flow-step="' +
          esc(step.id) +
          '"' +
          (i ? " hidden" : "") +
          ">" +
          html +
          "</section>"
        );
      })
      .join("\n");
    var runtime =
      (window.veComponents && window.veComponents.publicRuntime) || "";
    return (
      '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' +
      esc(storage.state.name) +
      "</title><style>[data-flow-step][hidden]{display:none!important}body{margin:0}</style></head><body>" +
      content +
      runtime +
      '<script src="/assets/funnel-runtime.js"></' +
      "script></body></html>"
    );
  }
  function nativeOfferStage() {
    var draftPreview = campaignContext.offer.is_published
      ? ""
      : ' srcdoc="' +
        esc(
          '<body style="font:18px system-ui;padding:40px;background:#06152d;color:white"><h1>' +
            esc(campaignContext.offer.title) +
            "</h1><p>R$ " +
            Number(campaignContext.offer.price).toFixed(2) +
            "</p><p>Oferta em rascunho. Edite os dados e o checkout na campanha. Ao publicar a página, este bloco carregará a oferta real.</p></body>",
        ) +
        '"';
    return {
      id: "oferta",
      name: "Upsell e checkout",
      raw: false,
      css: "body{margin:0;background:#06152d}iframe[data-campaign-offer]{display:block;width:100%;min-height:900px;border:0}",
      html:
        '<iframe data-campaign-offer="true"' +
        draftPreview +
        ' src="' +
        esc(campaignContext.offer.public_url + "?native=1") +
        '" title="Oferta e checkout da campanha" style="width:100%;min-height:900px;border:0"></iframe>',
    };
  }
  function attachCampaignOffer() {
    var offerStep = flow.steps.findIndex(function (s) {
      return s.id === "oferta";
    });
    if (offerStep >= 0) flow.steps[offerStep] = nativeOfferStage();
    flow.campaignMechanic =
      flow.campaignMechanic ||
      { raspadinha: "scratch", roleta: "wheel" }[flow.template] ||
      campaignContext.offer.mechanic ||
      "direct";
  }
  function createCampaignFlow(mechanic) {
    flow = veTemplates.create(
      mechanic === "wheel"
        ? "roleta"
        : mechanic === "scratch"
          ? "raspadinha"
          : "funil",
    );
    flow.campaignMechanic = mechanic;
    // A entrada é a mecânica escolhida; o destino é a oferta real desta campanha.
    flow.steps = flow.steps.filter(function (s) {
      return mechanic === "direct"
        ? s.id === "oferta"
        : ["raspar", "girar", "resultado", "oferta"].indexOf(s.id) !== -1;
    });
    attachCampaignOffer();
    active = 0;
    storage.state.name = campaignContext.campaign.title;
    $("ve-page-name").value = storage.state.name;
    openStep();
    storage.markDirty();
  }
  async function boot() {
    try {
      var pageId = Number(params.get("id") || 0);
      if (storage.state.campaignId) {
        campaignContext = await libraryRequest("load-campaign");
        if (!pageId && !params.get("template_id") && campaignContext.page)
          pageId = Number(campaignContext.page.id);
      }
      var loaded = await storage.load(pageId);
      if (!campaignContext && storage.state.campaignId)
        campaignContext = await libraryRequest("load-campaign");
      if (campaignContext) {
        $("ve-campaign-context").hidden = false;
        $("ve-campaign-context").textContent =
          "Campanha: " +
          campaignContext.campaign.title +
          ". Oferta: " +
          campaignContext.offer.title;
        document.querySelector(".ve-top > a").href =
          "index.html?workspace_id=" +
          encodeURIComponent(storage.state.workspaceId) +
          "&campaign_id=" +
          encodeURIComponent(storage.state.campaignId);
        $("ve-edit-campaign-offer").hidden = false;
        $("ve-edit-campaign-offer").href =
          "editor.php?workspace_id=" +
          encodeURIComponent(storage.state.workspaceId) +
          "&campaign_id=" +
          encodeURIComponent(storage.state.campaignId);
      }
      if (!storage.state.id && params.get("template_id")) {
        var data = await libraryRequest("load-template", {
          template_id: Number(params.get("template_id")),
        });
        flow = JSON.parse(
          JSON.stringify(data.template.project_data.upscaleFlow),
        );
        if (campaignContext) attachCampaignOffer();
        active = 0;
        storage.state.name = data.template.name + " (cópia)";
        storage.state.slug = "";
        $("ve-page-name").value = storage.state.name;
        $("ve-page-slug").value = "";
        openStep();
        storage.markDirty();
      } else if (campaignContext) {
        var mechanic =
          params.get("mechanic") ||
          flow.campaignMechanic ||
          campaignContext.offer.mechanic ||
          "scratch";
        if (["scratch", "wheel", "direct"].indexOf(mechanic) === -1)
          mechanic = "scratch";
        if (
          !storage.state.id ||
          (params.has("mechanic") &&
            flow.campaignMechanic !== mechanic &&
            confirm(
              "Alterar o tipo de funil? As etapas atuais serão substituídas.",
            ))
        )
          createCampaignFlow(mechanic);
      } else if (!storage.state.id) {
        $("ve-template-dialog").showModal();
        refreshLibrary();
      }
    } catch (e) {
      toast(e.message);
      $("ve-status").textContent =
        "Não foi possível carregar a campanha/página.";
    }
  }
  async function libraryRequest(action, body) {
    var headers = { "Content-Type": "application/json" };
    if (token()) headers.Authorization = "Bearer " + token();
    var response = await fetch(
      "../api/admin/visual-editor.php?action=" + action,
      {
        method: "POST",
        headers: headers,
        body: JSON.stringify(
          Object.assign(
            {
              workspace_id: storage.state.workspaceId,
              campaign_id: storage.state.campaignId,
            },
            body || {},
          ),
        ),
      },
    );
    var data = await response.json();
    if (!response.ok || data.status !== "success")
      throw new Error(data.message || "Falha ao acessar modelos.");
    return data;
  }
  async function refreshLibrary() {
    $("ve-library-status").textContent = "Carregando seus modelos…";
    $("ve-library").replaceChildren();
    try {
      var data = await libraryRequest("list-templates");
      $("ve-library-status").textContent = data.templates.length
        ? "Cada modelo cria uma nova página independente."
        : "Ainda não há modelos salvos. Use “Salvar como modelo” no editor.";
      data.templates.forEach(function (t) {
        var card = document.createElement("article");
        card.className = "ve-library-card";
        card.dataset.templateId = t.id;
        if (t.cover_url) {
          var image = document.createElement("img");
          image.src = t.cover_url;
          image.alt = "";
          image.loading = "lazy";
          image.referrerPolicy = "no-referrer";
          card.appendChild(image);
        }
        var name = document.createElement("strong");
        name.textContent = t.name;
        card.appendChild(name);
        var category = document.createElement("small");
        category.textContent = t.category;
        card.appendChild(category);
        var actions = document.createElement("div");
        actions.className = "ve-library-actions";
        var use = document.createElement("button");
        use.textContent = "Criar página";
        use.onclick = function () {
          // Abre uma nova página, sem substituir nem descartar o editor atual.
          var url =
            "visual-editor.html?workspace_id=" +
            encodeURIComponent(storage.state.workspaceId) +
            "&template_id=" +
            encodeURIComponent(t.id) +
            (storage.state.campaignId
              ? "&campaign_id=" + encodeURIComponent(storage.state.campaignId)
              : "");
          window.open(url, "_blank", "noopener");
        };
        var remove = document.createElement("button");
        remove.textContent = "Excluir";
        remove.onclick = async function () {
          if (
            !confirm(
              "Excluir este modelo? As páginas já criadas a partir dele serão preservadas.",
            )
          )
            return;
          remove.disabled = true;
          try {
            await libraryRequest("delete-template", { template_id: t.id });
            await refreshLibrary();
          } catch (e) {
            toast(e.message);
            remove.disabled = false;
          }
        };
        actions.append(use, remove);
        card.appendChild(actions);
        $("ve-library").appendChild(card);
      });
    } catch (e) {
      $("ve-library-status").textContent = e.message;
    }
  }
  function chooseTemplate(id) {
    if (
      flow &&
      storage.state.id &&
      !confirm("Substituir as etapas atuais pelo modelo?")
    )
      return;
    flow = window.veTemplates.create(id);
    if (campaignContext) attachCampaignOffer();
    active = 0;
    openStep();
    storage.markDirty();
    $("ve-template-dialog").close();
  }
  function status(ev) {
    var labels = {
      loading: "Carregando…",
      loaded: "Página carregada",
      dirty: "Alterações não salvas",
      saving: "Salvando…",
      saved: "Rascunho salvo",
      publishing: "Publicando…",
      published: "Página publicada",
      conflict: "Conflito: recarregue antes de salvar",
      error: "Erro ao salvar",
    };
    $("ve-status").textContent = labels[ev.phase] || ev.phase;
    if (ev.phase === "error" || ev.phase === "conflict")
      toast(ev.message || labels[ev.phase]);
    if (ev.data && ev.data.slug && !$("ve-page-slug").matches(":focus"))
      $("ve-page-slug").value = ev.data.slug;
    if (ev.phase === "saved") {
      var url = new URL(location.href);
      url.searchParams.set("id", ev.data.id);
      history.replaceState(null, "", url);
    }
    if (ev.phase === "published") {
      var link = $("ve-public-link");
      link.href = ev.data.public_url;
      link.hidden = false;
      toast("Página publicada. Use “Abrir publicada” para conferir.");
    }
  }
  window.addEventListener("DOMContentLoaded", function () {
    if (window.grapesjs) {
      editor = grapesjs.init({
        container: "#gjs",
        height: "100%",
        width: "100%",
        storageManager: false,
        panels: { defaults: [] },
        blockManager: { appendTo: "#ve-blocks" },
        traitManager: { appendTo: "#ve-traits" },
        layerManager: { appendTo: "#ve-layers" },
        selectorManager: { appendTo: "#ve-styles" },
        styleManager: {
          appendTo: "#ve-styles",
          sectors: [
            {
              name: "Layout",
              open: true,
              buildProps: [
                "display",
                "width",
                "max-width",
                "min-height",
                "padding",
                "margin",
                "text-align",
              ],
            },
            {
              name: "Texto",
              buildProps: [
                "font-family",
                "font-size",
                "font-weight",
                "line-height",
                "color",
              ],
            },
            {
              name: "Aparência",
              buildProps: [
                "background-color",
                "border",
                "border-radius",
                "box-shadow",
              ],
            },
          ],
        },
        deviceManager: {
          devices: [
            { name: "Desktop", width: "" },
            { name: "Tablet", width: "768px" },
            { name: "Celular", width: "390px" },
          ],
        },
        assetManager: {
          upload:
            "../api/admin/upload.php?workspace_id=" +
            encodeURIComponent(params.get("workspace_id") || "1"),
          uploadName: "file",
          headers: token() ? { Authorization: "Bearer " + token() } : {},
          multiUpload: false,
          autoAdd: false,
        },
      });
      editor.on("asset:upload:response", function (data) {
        if (data.status === "success" && data.url)
          editor.AssetManager.add({ src: data.url });
        else toast(data.message || "Não foi possível enviar a imagem.");
      });
      if (window.veComponents) veComponents.register(editor);
      if (window.veBlocks) veBlocks.register(editor);
      editor.on("update", editorChanged);
      editor.on("component:selected", function (component) {
        if (!component) return;
        var traits = component.get("traits");
        ["data-flow-next", "data-flow-goto"].forEach(function (name) {
          if (!traits.where({ name: name }).length)
            component.addTrait({
              name: name,
              label:
                name === "data-flow-next"
                  ? "Ir para próxima etapa"
                  : "Ir para etapa (ID)",
              type: name === "data-flow-next" ? "checkbox" : "select",
              options:
                name === "data-flow-goto"
                  ? [{ id: "", name: "Nenhuma" }].concat(
                      flow.steps.map(function (s) {
                        return { id: s.id, name: s.name };
                      }),
                    )
                  : undefined,
            });
        });
      });
    } else {
      toast(
        "Editor visual indisponível. Você pode inserir HTML pronto e publicar.",
      );
      $("ve-mode-visual").disabled = true;
    }
    storage = veStorage.create({
      id: Number(params.get("id") || 0),
      workspaceId: Number(params.get("workspace_id") || 1),
      campaignId: Number(params.get("campaign_id") || 0),
      token: token(),
      onStatus: status,
      serialize: function () {
        var html = documentHtml();
        return { project_data: { upscaleFlow: flow }, html: html, css: "" };
      },
      onLoad: function (d) {
        $("ve-page-name").value = d.name;
        $("ve-page-slug").value = d.slug;
        flow = d.project_data && d.project_data.upscaleFlow;
        if (!flow || !Array.isArray(flow.steps) || !flow.steps.length) {
          if (d.id) {
            flow = {
              template: "legacy",
              steps: [
                {
                  id: "inicio",
                  name: "Página principal",
                  raw: !d.project_data,
                  html: d.legacy_html || "",
                  css: "",
                  project: d.project_data || null,
                },
              ],
            };
          } else flow = veTemplates.create("landing");
        }
        active = 0;
        openStep();
      },
    });
    window.upscalePageEditor = {
      get editor() {
        return editor;
      },
      get flow() {
        return flow;
      },
      storage: storage,
    };
    veTemplates.catalog.forEach(function (t) {
      var b = document.createElement("button");
      b.innerHTML =
        esc(t.icon) +
        "<strong>" +
        esc(t.name) +
        "</strong><span>" +
        esc(t.description) +
        "</span>";
      b.dataset.template = t.id;
      b.onclick = function () {
        chooseTemplate(t.id);
      };
      $("ve-template-list").appendChild(b);
    });
    $("ve-btn-templates").onclick = function () {
      $("ve-template-dialog").showModal();
      refreshLibrary();
    };
    $("ve-save-template").onclick = function () {
      $("ve-template-name").value = storage.state.name;
      $("ve-template-error").textContent = "";
      $("ve-save-template-dialog").showModal();
    };
    $("ve-cancel-template").onclick = function () {
      $("ve-save-template-dialog").close();
    };
    $("ve-template-form").onsubmit = async function (event) {
      event.preventDefault();
      $("ve-confirm-template").disabled = true;
      $("ve-template-error").textContent = "";
      try {
        capture();
        await libraryRequest("save-template", {
          name: $("ve-template-name").value,
          category: $("ve-template-category").value,
          cover_url: $("ve-template-cover").value,
          project_data: { upscaleFlow: flow },
        });
        $("ve-save-template-dialog").close();
        toast(
          "Modelo salvo. Encontre-o em Modelos e HTML pronto → Meus modelos.",
        );
      } catch (e) {
        $("ve-template-error").textContent = e.message;
      } finally {
        $("ve-confirm-template").disabled = false;
      }
    };
    $("ve-close-templates").onclick = function () {
      $("ve-template-dialog").close();
    };
    $("ve-page-name").oninput = function () {
      storage.setName(this.value);
    };
    $("ve-page-slug").oninput = function () {
      storage.setSlug(this.value);
    };
    $("ve-step-name").oninput = function () {
      flow.steps[active].name = this.value;
      renderSteps();
      dirty();
      $("ve-step-name").focus();
    };
    $("ve-add-step").onclick = function () {
      capture();
      flow.steps.push({
        id: "etapa-" + Date.now(),
        name: "Nova etapa",
        html: '<main class="flow-card"><h1>Nova etapa</h1><p>Personalize o conteúdo desta etapa.</p><a href="#" class="cta" data-flow-next>Continuar →</a></main>',
        css: flow.steps[active].css || "",
      });
      active = flow.steps.length - 1;
      openStep();
      dirty();
    };
    $("ve-delete-step").onclick = function () {
      if (flow.steps.length < 2 || !confirm("Excluir esta etapa?")) return;
      flow.steps.splice(active, 1);
      active = Math.min(active, flow.steps.length - 1);
      openStep();
      dirty();
    };
    function move(offset) {
      capture();
      var target = active + offset;
      if (target < 0 || target >= flow.steps.length) return;
      var s = flow.steps.splice(active, 1)[0];
      flow.steps.splice(target, 0, s);
      active = target;
      openStep();
      dirty();
    }
    $("ve-step-up").onclick = function () {
      move(-1);
    };
    $("ve-step-down").onclick = function () {
      move(1);
    };
    $("ve-mode-html").onclick = function () {
      if (mode === "html") return;
      capture();
      var s = flow.steps[active];
      $("ve-html").value = "<style>" + s.css + "</style>\n" + s.html;
      s.raw = true;
      setMode("html");
      dirty();
    };
    $("ve-mode-visual").onclick = function () {
      if (mode === "html") {
        $("ve-convert").click();
      }
    };
    $("ve-html").oninput = dirty;
    $("ve-convert").onclick = function () {
      if (!editor) {
        toast("GrapesJS não está disponível. Continue no modo HTML.");
        return;
      }
      if (
        !confirm(
          "Converter o HTML em blocos editáveis? Scripts e recursos do documento podem precisar de ajuste. O HTML original será substituído na próxima gravação.",
        )
      )
        return;
      capture();
      var s = flow.steps[active],
        doc = new DOMParser().parseFromString(s.html, "text/html");
      s.html = doc.body.innerHTML;
      s.css = Array.from(doc.querySelectorAll("style"))
        .map(function (x) {
          return x.textContent;
        })
        .join("\n");
      s.raw = false;
      delete s.project;
      openStep();
      dirty();
    };
    $("ve-btn-save").onclick = function () {
      storage.saveDraft().catch(function () {});
    };
    $("ve-btn-publish").onclick = async function () {
      this.disabled = true;
      try {
        await storage.publish();
      } catch (e) {
      } finally {
        this.disabled = false;
      }
    };
    $("ve-btn-preview").onclick = function () {
      $("ve-preview").srcdoc = documentHtml(true);
      $("ve-preview-dialog").showModal();
    };
    $("ve-close-preview").onclick = function () {
      $("ve-preview-dialog").close();
      $("ve-preview").srcdoc = "";
    };
    document.querySelectorAll("[data-device]").forEach(function (b) {
      b.onclick = function () {
        if (editor) editor.setDevice(b.dataset.device);
      };
    });
    $("ve-undo").onclick = function () {
      if (editor) editor.UndoManager.undo();
    };
    $("ve-redo").onclick = function () {
      if (editor) editor.UndoManager.redo();
    };
    boot();
  });
})();
