(function (window) {
  "use strict";
  function create(opts) {
    var state = {
      id: opts.id || 0,
      workspaceId: opts.workspaceId || 1,
      revision: 0,
      campaignId: opts.campaignId || null,
      name: "Nova página",
      slug: "",
      dirty: false,
      token: opts.token || "",
    };
    var timer = null,
      inflight = null,
      version = 0;
    function status(phase, data) {
      opts.onStatus(Object.assign({ phase: phase }, data || {}));
    }
    async function request(action, body) {
      var headers = { "Content-Type": "application/json" };
      if (state.token) headers.Authorization = "Bearer " + state.token;
      var r = await fetch("../api/admin/visual-editor.php?action=" + action, {
        method: "POST",
        headers: headers,
        body: JSON.stringify(
          Object.assign(
            {
              action: action,
              workspace_id: state.workspaceId,
              campaign_id: state.campaignId,
            },
            body,
          ),
        ),
      });
      var data = await r.json();
      if (!r.ok || data.status !== "success") {
        var err = new Error(data.message || "HTTP " + r.status);
        err.conflict = r.status === 409;
        throw err;
      }
      return data;
    }
    function report(err) {
      status(err.conflict ? "conflict" : "error", { message: err.message });
    }
    async function load(id) {
      clearTimeout(timer);
      status("loading");
      try {
        var d = await request("load", { id: id || 0 });
        state.id = d.id;
        state.name = d.name;
        state.slug = d.slug;
        state.revision = d.revision;
        state.campaignId = d.campaign_id || state.campaignId;
        await opts.onLoad(d);
        state.dirty = false;
        status("loaded", { data: d });
        return d;
      } catch (e) {
        report(e);
        throw e;
      }
    }
    function markDirty() {
      version++;
      state.dirty = true;
      status("dirty");
      clearTimeout(timer);
      timer = setTimeout(function () {
        saveDraft().catch(function () {});
      }, 3000);
    }
    async function saveDraft() {
      clearTimeout(timer);
      if (inflight) {
        await inflight;
        if (state.dirty) return saveDraft();
        return;
      }
      var current = version,
        payload = opts.serialize();
      status("saving");
      inflight = (async function () {
        try {
          var d = await request(
            "save-draft",
            Object.assign(
              {
                id: state.id,
                name: state.name,
                slug: state.slug || state.name,
                expected_revision: state.revision,
              },
              payload,
            ),
          );
          state.id = d.id;
          state.slug = d.slug;
          state.revision = d.revision;
          state.dirty = version !== current;
          status("saved", { data: d });
          return d;
        } catch (e) {
          report(e);
          throw e;
        }
      })();
      try {
        return await inflight;
      } finally {
        inflight = null;
        if (state.dirty && version !== current) {
          clearTimeout(timer);
          timer = setTimeout(function () {
            saveDraft().catch(function () {});
          }, 3000);
        }
      }
    }
    async function publish() {
      try {
        if (inflight) await inflight;
        // Capture even edits whose GrapesJS update event has not fired yet.
        await saveDraft();
        if (state.dirty) await saveDraft();
        status("publishing");
        var d = await request("publish", {
          id: state.id,
          expected_revision: state.revision,
          title: state.name,
        });
        state.revision = d.revision;
        status("published", { data: d });
        return d;
      } catch (e) {
        report(e);
        throw e;
      }
    }
    window.addEventListener("beforeunload", function (e) {
      if (state.dirty) {
        e.preventDefault();
        e.returnValue = "";
      }
    });
    return {
      state: state,
      load: load,
      saveDraft: saveDraft,
      publish: publish,
      markDirty: markDirty,
      setName: function (n) {
        state.name = n;
        markDirty();
      },
      setSlug: function (s) {
        state.slug = s;
        markDirty();
      },
    };
  }
  window.veStorage = { create: create };
})(window);
