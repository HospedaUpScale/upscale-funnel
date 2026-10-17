/* Efeitos sintetizados no navegador: nenhum download de áudio ou autoplay. */
(function () {
  "use strict";
  var Context = window.AudioContext || window.webkitAudioContext;
  var context = null,
    master = null,
    unlocked = false,
    enabled = true,
    volume = 0.2,
    muted = false,
    timer = null,
    lastScratch = 0;
  try {
    muted = localStorage.getItem("upscale_sound_muted") === "1";
  } catch (e) {}
  var button = document.createElement("button");
  button.type = "button";
  button.id = "experience-sound-toggle";
  button.style.cssText =
    "display:block;margin:12px auto;padding:7px 12px;border:1px solid #ffffff38;border-radius:20px;background:#10243d;color:#dce8ff;font:12px system-ui;cursor:pointer;";
  var root = document.getElementById("rp");
  (root || document.body).appendChild(button);
  function update() {
    button.hidden = !enabled || !Context;
    button.style.display = button.hidden ? "none" : "block";
    button.textContent = muted ? "Som desativado" : "Som ativado";
    button.setAttribute(
      "aria-label",
      muted ? "Ativar efeitos sonoros" : "Silenciar efeitos sonoros",
    );
    button.setAttribute("aria-pressed", String(!muted));
    if (master) master.gain.value = enabled && !muted ? volume : 0;
  }
  function stop() {
    clearTimeout(timer);
    timer = null;
  }
  function activate() {
    if (!enabled || muted || !Context) return;
    try {
      if (!context) {
        context = new Context();
        master = context.createGain();
        master.connect(context.destination);
        update();
      }
      unlocked = true;
      if (context.state === "suspended") context.resume().catch(function () {});
    } catch (e) {
      unlocked = false;
    }
  }
  function canPlay() {
    return (
      enabled &&
      !muted &&
      unlocked &&
      context &&
      context.state === "running" &&
      !document.hidden
    );
  }
  function tone(hz, length, delay, type) {
    if (!canPlay()) return;
    var at = context.currentTime + (delay || 0),
      osc = context.createOscillator(),
      gain = context.createGain();
    osc.type = type || "sine";
    osc.frequency.value = hz;
    gain.gain.setValueAtTime(0.001, at);
    gain.gain.exponentialRampToValueAtTime(0.3, at + 0.008);
    gain.gain.exponentialRampToValueAtTime(0.001, at + length);
    osc.connect(gain);
    gain.connect(master);
    osc.onended = function () {
      osc.disconnect();
      gain.disconnect();
    };
    osc.start(at);
    osc.stop(at + length + 0.02);
  }
  function scratch() {
    if (!canPlay() || performance.now() - lastScratch < 45) return;
    lastScratch = performance.now();
    var length = 0.06,
      buffer = context.createBuffer(
        1,
        Math.ceil(context.sampleRate * length),
        context.sampleRate,
      ),
      data = buffer.getChannelData(0);
    for (var i = 0; i < data.length; i++)
      data[i] = (Math.random() * 2 - 1) * (1 - i / data.length);
    var source = context.createBufferSource(),
      filter = context.createBiquadFilter(),
      gain = context.createGain();
    source.buffer = buffer;
    filter.type = "bandpass";
    filter.frequency.value = 1700;
    filter.Q.value = 0.7;
    gain.gain.value = 0.25;
    source.connect(filter);
    filter.connect(gain);
    gain.connect(master);
    source.onended = function () {
      source.disconnect();
      filter.disconnect();
      gain.disconnect();
    };
    source.start();
  }
  function spin(duration) {
    stop();
    activate();
    var started = performance.now();
    function tick() {
      var elapsed = performance.now() - started;
      if (elapsed >= duration || !enabled || muted || document.hidden) {
        stop();
        return;
      }
      tone(1000 - (elapsed / duration) * 350, 0.025, 0, "triangle");
      timer = setTimeout(tick, 40 + Math.pow(elapsed / duration, 2) * 150);
    }
    tick();
  }
  function reveal() {
    stop();
    if (!canPlay()) {
      if (unlocked && context && context.state === "suspended")
        context
          .resume()
          .then(function () {
            if (context.state === "running") reveal();
          })
          .catch(function () {});
      return;
    }
    [523.25, 659.25, 783.99, 1046.5].forEach(function (hz, i) {
      tone(hz, 0.19, i * 0.09);
    });
  }
  button.addEventListener("click", function () {
    muted = !muted;
    try {
      localStorage.setItem("upscale_sound_muted", muted ? "1" : "0");
    } catch (e) {}
    if (muted) stop();
    else activate();
    update();
  });
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) stop();
  });
  window.addEventListener("pagehide", stop);
  window.upscaleSounds = {
    activate: activate,
    scratch: scratch,
    spin: spin,
    reveal: reveal,
    stop: stop,
    configure: function (config) {
      enabled = config.enabled !== false;
      var next = Number(config.volume);
      volume = isFinite(next) ? Math.max(0, Math.min(1, next)) : 0.2;
      if (!enabled) stop();
      update();
    },
  };
  update();
})();
