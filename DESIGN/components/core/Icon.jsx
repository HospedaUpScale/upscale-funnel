import React from "react";

const LUCIDE = "https://cdn.jsdelivr.net/npm/lucide-static@0.544.0/icons/";
const cache = new Map();

function load(name) {
  if (!cache.has(name)) {
    cache.set(name, fetch(LUCIDE + name + ".svg")
      .then((r) => (r.ok ? r.text() : Promise.reject(new Error(r.status))))
      .then((t) => t.replace(/<!--[\s\S]*?-->/g, "").trim())
      .catch(() => null));
  }
  return cache.get(name);
}

export function Icon({ name, size = 18, strokeWidth = 1.75, color = "currentColor", style, ...rest }) {
  const [markup, setMarkup] = React.useState(null);
  React.useEffect(() => {
    let live = true;
    load(name).then((t) => { if (live) setMarkup(t); });
    return () => { live = false; };
  }, [name]);

  const base = {
    display: "inline-flex", alignItems: "center", justifyContent: "center", flex: "none",
    width: size, height: size, color, ...style,
  };

  if (!markup) {
    // While the glyph is in flight, hold the box with a masked placeholder so layout never shifts.
    const url = `url("${LUCIDE}${name}.svg")`;
    return <span aria-hidden="true" data-icon={name} style={{ ...base, background: color, WebkitMaskImage: url, maskImage: url, WebkitMaskSize: "contain", maskSize: "contain", WebkitMaskRepeat: "no-repeat", maskRepeat: "no-repeat", WebkitMaskPosition: "center", maskPosition: "center" }} {...rest} />;
  }

  const svg = markup
    .replace(/\swidth="[^"]*"/, "")
    .replace(/\sheight="[^"]*"/, "")
    .replace(/stroke-width="[^"]*"/, `stroke-width="${strokeWidth}"`)
    .replace("<svg", `<svg width="${size}" height="${size}" style="display:block"`);

  return <span aria-hidden="true" data-icon={name} style={base} dangerouslySetInnerHTML={{ __html: svg }} {...rest} />;
}
