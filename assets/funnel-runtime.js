/* Interações das páginas publicadas. Sem dependências do painel. */
(function () {
  "use strict";
  // Mantém o contexto do pedido e as UTMs ao entrar no checkout nativo.
  document
    .querySelectorAll("iframe[data-campaign-offer]")
    .forEach(function (frame) {
      try {
        var target = new URL(frame.getAttribute("src"), location.href);
        if (target.origin !== location.origin) return;
        var original = target.href;
        new URLSearchParams(location.search).forEach(function (value, key) {
          if (!target.searchParams.has(key))
            target.searchParams.set(key, value);
        });
        if (target.href !== original) frame.src = target.href;
      } catch (e) {}
    });
  var steps = Array.from(document.querySelectorAll("[data-flow-step]"));
  function show(index) {
    if (index < 0 || index >= steps.length) return;
    steps.forEach(function (s, i) {
      s.hidden = i !== index;
    });
    window.scrollTo(0, 0);
    initScratch();
  }
  document.addEventListener("click", function (e) {
    var next = e.target.closest("[data-flow-next]"),
      go = e.target.closest("[data-flow-goto]");
    if (next || go) {
      e.preventDefault();
      var current = steps.findIndex(function (s) {
        return !s.hidden;
      });
      show(
        go
          ? steps.findIndex(function (s) {
              return s.dataset.flowStep === go.dataset.flowGoto;
            })
          : current + 1,
      );
    }
    var spin = e.target.closest("[data-spin]");
    if (spin && !spin.disabled) {
      e.preventDefault();
      var step = spin.closest("[data-flow-step]") || document;
      var wheel = step.querySelector("[data-wheel]");
      if (!wheel) return;
      spin.disabled = true;
      wheel.style.transform = "rotate(1440deg)";
      setTimeout(function () {
        spin.disabled = false;
        wheel.style.transform = "";
        var index = steps.indexOf(step);
        show(index + 1);
      }, 2300);
    }
    var checkout = e.target.closest("[data-checkout-link]");
    if (checkout && !checkout.getAttribute("href")) {
      e.preventDefault();
      alert(
        "O responsável pela página ainda não configurou o link de checkout.",
      );
    }
  });
  function initScratch() {
    document
      .querySelectorAll("[data-scratch] canvas")
      .forEach(function (canvas) {
        if (canvas.dataset.ready || !canvas.offsetWidth) return;
        canvas.dataset.ready = "1";
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        var ctx = canvas.getContext("2d");
        ctx.fillStyle = "#819b94";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#fff";
        ctx.font = "bold 21px system-ui";
        ctx.textAlign = "center";
        ctx.fillText("RASPE AQUI", canvas.width / 2, canvas.height / 2);
        var down = false;
        function erase(e) {
          if (!down) return;
          var r = canvas.getBoundingClientRect();
          ctx.globalCompositeOperation = "destination-out";
          ctx.beginPath();
          ctx.arc(
            ((e.clientX - r.left) * canvas.width) / r.width,
            ((e.clientY - r.top) * canvas.height) / r.height,
            24,
            0,
            Math.PI * 2,
          );
          ctx.fill();
        }
        canvas.addEventListener("pointerdown", function (e) {
          down = true;
          canvas.setPointerCapture(e.pointerId);
          erase(e);
        });
        canvas.addEventListener("pointermove", erase);
        canvas.addEventListener("pointerup", function () {
          down = false;
        });
        canvas.addEventListener("pointercancel", function () {
          down = false;
        });
      });
  }
  if (steps.length) show(0);
  else initScratch();
})();
