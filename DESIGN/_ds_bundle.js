/* @ds-bundle: {"format":4,"namespace":"UpscaleHubDesignSystem_b4eac1","components":[{"name":"Avatar","sourcePath":"components/core/Avatar.jsx"},{"name":"Badge","sourcePath":"components/core/Badge.jsx"},{"name":"Button","sourcePath":"components/core/Button.jsx"},{"name":"Icon","sourcePath":"components/core/Icon.jsx"},{"name":"IconButton","sourcePath":"components/core/IconButton.jsx"},{"name":"ProgressBar","sourcePath":"components/core/ProgressBar.jsx"},{"name":"Switch","sourcePath":"components/core/Switch.jsx"},{"name":"Tabs","sourcePath":"components/core/Tabs.jsx"},{"name":"AreaChart","sourcePath":"components/data/AreaChart.jsx"},{"name":"Sparkline","sourcePath":"components/data/AreaChart.jsx"},{"name":"BarChart","sourcePath":"components/data/BarChart.jsx"},{"name":"ChartLegend","sourcePath":"components/data/BarChart.jsx"},{"name":"DataTable","sourcePath":"components/data/DataTable.jsx"},{"name":"TransactionCell","sourcePath":"components/data/DataTable.jsx"},{"name":"Amount","sourcePath":"components/data/DataTable.jsx"},{"name":"DonutChart","sourcePath":"components/data/DonutChart.jsx"},{"name":"CategoryLegend","sourcePath":"components/data/DonutChart.jsx"},{"name":"AssistantOrb","sourcePath":"components/finance/AssistantComposer.jsx"},{"name":"AssistantComposer","sourcePath":"components/finance/AssistantComposer.jsx"},{"name":"CreditCardItem","sourcePath":"components/finance/CreditCardItem.jsx"},{"name":"ActionTile","sourcePath":"components/finance/CreditCardItem.jsx"},{"name":"ExchangePanel","sourcePath":"components/finance/ExchangePanel.jsx"},{"name":"SuggestionCard","sourcePath":"components/finance/SuggestionCard.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"Input","sourcePath":"components/forms/Input.jsx"},{"name":"Radio","sourcePath":"components/forms/Radio.jsx"},{"name":"SearchField","sourcePath":"components/forms/SearchField.jsx"},{"name":"Select","sourcePath":"components/forms/Select.jsx"},{"name":"AccountSwitcher","sourcePath":"components/navigation/SidebarNav.jsx"},{"name":"SidebarNav","sourcePath":"components/navigation/SidebarNav.jsx"},{"name":"TopBar","sourcePath":"components/navigation/TopBar.jsx"},{"name":"BalanceCard","sourcePath":"components/surfaces/BalanceCard.jsx"},{"name":"Card","sourcePath":"components/surfaces/Card.jsx"},{"name":"CardFooterLink","sourcePath":"components/surfaces/Card.jsx"},{"name":"MetricCard","sourcePath":"components/surfaces/MetricCard.jsx"},{"name":"StatTile","sourcePath":"components/surfaces/StatTile.jsx"},{"name":"UpgradeCard","sourcePath":"components/surfaces/UpgradeCard.jsx"}],"sourceHashes":{"components/core/Avatar.jsx":"a7e6ed48abe1","components/core/Badge.jsx":"3e8460b37416","components/core/Button.jsx":"924ca61c4d5a","components/core/Icon.jsx":"70dab904ec5b","components/core/IconButton.jsx":"1e561fcb5dd3","components/core/ProgressBar.jsx":"19ce02a6db56","components/core/Switch.jsx":"c77bc6fb2326","components/core/Tabs.jsx":"0dcb74f5f14f","components/data/AreaChart.jsx":"b183d5048e1f","components/data/BarChart.jsx":"99e864270ae3","components/data/DataTable.jsx":"26665e97e041","components/data/DonutChart.jsx":"3dfe054e6024","components/finance/AssistantComposer.jsx":"15d949d5314c","components/finance/CreditCardItem.jsx":"33a70531673f","components/finance/ExchangePanel.jsx":"db9b61c863ca","components/finance/SuggestionCard.jsx":"57d35dadb1a4","components/forms/Checkbox.jsx":"f779368e030f","components/forms/Input.jsx":"20186d10eca8","components/forms/Radio.jsx":"5b8d74e71880","components/forms/SearchField.jsx":"c64a3a1f9241","components/forms/Select.jsx":"555e31efee36","components/navigation/SidebarNav.jsx":"a99dda3396bf","components/navigation/TopBar.jsx":"d71f70becf1d","components/surfaces/BalanceCard.jsx":"019f86a1e206","components/surfaces/Card.jsx":"fd88472ca361","components/surfaces/MetricCard.jsx":"b32f93c8b1a3","components/surfaces/StatTile.jsx":"b03dc649bdfc","components/surfaces/UpgradeCard.jsx":"356b988bfac4","ui_kits/dashboard/AiAssistant.jsx":"17ebde3b31f4","ui_kits/dashboard/AppShell.jsx":"6a80f10191b9","ui_kits/dashboard/DashboardHome.jsx":"6e05d3d3f098","ui_kits/dashboard/Transactions.jsx":"df9e2614f5c2","ui_kits/dashboard/Wallet.jsx":"459bb1303584","ui_kits/dashboard/data.js":"a88607dfb627"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.UpscaleHubDesignSystem_b4eac1 = window.UpscaleHubDesignSystem_b4eac1 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/core/Avatar.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const SIZES = {
  xs: 20,
  sm: 26,
  md: 32,
  lg: 40,
  xl: 56
};
function Avatar({
  src,
  name = "",
  size = "md",
  ring = false,
  status,
  style,
  ...rest
}) {
  const px = SIZES[size] || SIZES.md;
  const initials = name.split(" ").filter(Boolean).slice(0, 2).map(w => w[0]).join("").toUpperCase();
  return /*#__PURE__*/React.createElement("span", _extends({
    style: {
      position: "relative",
      display: "inline-block",
      flex: "none",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      width: px,
      height: px,
      overflow: "hidden",
      borderRadius: "var(--radius-pill)",
      background: "var(--gradient-orb)",
      color: "var(--white)",
      font: `var(--weight-semibold) ${Math.round(px * 0.36)}px/1 var(--font-ui)`,
      boxShadow: ring ? "0 0 0 2px var(--white), 0 0 0 3.5px var(--blue-600)" : "none"
    }
  }, src ? /*#__PURE__*/React.createElement("img", {
    src: src,
    alt: name,
    style: {
      width: "100%",
      height: "100%",
      objectFit: "cover"
    }
  }) : initials), status ? /*#__PURE__*/React.createElement("span", {
    style: {
      position: "absolute",
      right: -1,
      bottom: -1,
      width: Math.max(7, px * 0.26),
      height: Math.max(7, px * 0.26),
      borderRadius: "var(--radius-pill)",
      boxShadow: "0 0 0 2px var(--white)",
      background: status === "online" ? "var(--green-600)" : status === "busy" ? "var(--red-600)" : "var(--gray-400)"
    }
  }) : null);
}
Object.assign(__ds_scope, { Avatar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Avatar.jsx", error: String((e && e.message) || e) }); }

// components/core/Icon.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const LUCIDE = "https://cdn.jsdelivr.net/npm/lucide-static@0.544.0/icons/";
const cache = new Map();
function load(name) {
  if (!cache.has(name)) {
    cache.set(name, fetch(LUCIDE + name + ".svg").then(r => r.ok ? r.text() : Promise.reject(new Error(r.status))).then(t => t.replace(/<!--[\s\S]*?-->/g, "").trim()).catch(() => null));
  }
  return cache.get(name);
}
function Icon({
  name,
  size = 18,
  strokeWidth = 1.75,
  color = "currentColor",
  style,
  ...rest
}) {
  const [markup, setMarkup] = React.useState(null);
  React.useEffect(() => {
    let live = true;
    load(name).then(t => {
      if (live) setMarkup(t);
    });
    return () => {
      live = false;
    };
  }, [name]);
  const base = {
    display: "inline-flex",
    alignItems: "center",
    justifyContent: "center",
    flex: "none",
    width: size,
    height: size,
    color,
    ...style
  };
  if (!markup) {
    // While the glyph is in flight, hold the box with a masked placeholder so layout never shifts.
    const url = `url("${LUCIDE}${name}.svg")`;
    return /*#__PURE__*/React.createElement("span", _extends({
      "aria-hidden": "true",
      "data-icon": name,
      style: {
        ...base,
        background: color,
        WebkitMaskImage: url,
        maskImage: url,
        WebkitMaskSize: "contain",
        maskSize: "contain",
        WebkitMaskRepeat: "no-repeat",
        maskRepeat: "no-repeat",
        WebkitMaskPosition: "center",
        maskPosition: "center"
      }
    }, rest));
  }
  const svg = markup.replace(/\swidth="[^"]*"/, "").replace(/\sheight="[^"]*"/, "").replace(/stroke-width="[^"]*"/, `stroke-width="${strokeWidth}"`).replace("<svg", `<svg width="${size}" height="${size}" style="display:block"`);
  return /*#__PURE__*/React.createElement("span", _extends({
    "aria-hidden": "true",
    "data-icon": name,
    style: base,
    dangerouslySetInnerHTML: {
      __html: svg
    }
  }, rest));
}
Object.assign(__ds_scope, { Icon });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Icon.jsx", error: String((e && e.message) || e) }); }

// components/core/Badge.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const TONES = {
  success: {
    background: "var(--status-success-bg)",
    color: "var(--status-success-fg)"
  },
  danger: {
    background: "var(--status-danger-bg)",
    color: "var(--status-danger-fg)"
  },
  warning: {
    background: "var(--status-warning-bg)",
    color: "var(--status-warning-fg)"
  },
  neutral: {
    background: "var(--status-neutral-bg)",
    color: "var(--status-neutral-fg)"
  },
  brand: {
    background: "var(--surface-brand)",
    color: "var(--text-on-brand)"
  },
  accent: {
    background: "var(--surface-accent)",
    color: "var(--text-on-accent)"
  },
  inverse: {
    background: "var(--ink-900)",
    color: "var(--white)"
  },
  outline: {
    background: "var(--white)",
    color: "var(--gray-600)",
    boxShadow: "inset 0 0 0 1px var(--border-default)"
  }
};
function Badge({
  children,
  tone = "neutral",
  icon,
  dot = false,
  size = "md",
  style,
  ...rest
}) {
  const t = TONES[tone] || TONES.neutral;
  const small = size === "sm";
  return /*#__PURE__*/React.createElement("span", _extends({
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: small ? 4 : 5,
      height: small ? 18 : 22,
      padding: small ? "0 7px" : "0 9px",
      borderRadius: "var(--radius-pill)",
      font: `var(--weight-medium) ${small ? "var(--text-2xs)" : "var(--text-xs)"}/1 var(--font-ui)`,
      letterSpacing: "var(--tracking-snug)",
      whiteSpace: "nowrap",
      ...t,
      ...style
    }
  }, rest), dot ? /*#__PURE__*/React.createElement("span", {
    style: {
      width: 5,
      height: 5,
      borderRadius: "var(--radius-pill)",
      background: "currentColor"
    }
  }) : null, icon ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: small ? 10 : 12
  }) : null, children);
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Badge.jsx", error: String((e && e.message) || e) }); }

// components/core/Button.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const SIZES = {
  sm: {
    height: "var(--control-height-sm)",
    padding: "0 12px",
    font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)",
    gap: 6,
    icon: 14
  },
  md: {
    height: "var(--control-height)",
    padding: "0 16px",
    font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)",
    gap: 8,
    icon: 16
  },
  lg: {
    height: "var(--control-height-lg)",
    padding: "0 22px",
    font: "var(--weight-semibold) var(--text-base)/1 var(--font-ui)",
    gap: 8,
    icon: 18
  }
};
const VARIANTS = {
  primary: {
    background: "var(--ink-900)",
    color: "var(--text-on-inverse)",
    border: "1px solid var(--ink-900)",
    boxShadow: "var(--shadow-sm)"
  },
  brand: {
    background: "var(--surface-brand)",
    color: "var(--text-on-brand)",
    border: "1px solid var(--surface-brand)",
    boxShadow: "var(--shadow-brand)"
  },
  secondary: {
    background: "var(--white)",
    color: "var(--ink-900)",
    border: "1px solid var(--border-default)",
    boxShadow: "var(--shadow-xs)"
  },
  soft: {
    background: "var(--surface-brand-soft)",
    color: "var(--blue-700)",
    border: "1px solid transparent",
    boxShadow: "none"
  },
  ghost: {
    background: "transparent",
    color: "var(--text-muted)",
    border: "1px solid transparent",
    boxShadow: "none"
  },
  danger: {
    background: "var(--red-100)",
    color: "var(--red-600)",
    border: "1px solid transparent",
    boxShadow: "none"
  }
};
const HOVER = {
  primary: {
    background: "var(--ink-800)"
  },
  brand: {
    background: "var(--blue-500)"
  },
  secondary: {
    background: "var(--surface-hover)"
  },
  soft: {
    background: "var(--blue-200)"
  },
  ghost: {
    background: "var(--surface-hover)",
    color: "var(--ink-900)"
  },
  danger: {
    background: "#FBDDDE"
  }
};
function Button({
  children,
  variant = "primary",
  size = "md",
  icon,
  iconRight,
  pill = true,
  disabled = false,
  fullWidth = false,
  style,
  onClick,
  type = "button",
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const [press, setPress] = React.useState(false);
  const s = SIZES[size] || SIZES.md;
  const v = VARIANTS[variant] || VARIANTS.primary;
  return /*#__PURE__*/React.createElement("button", _extends({
    type: type,
    disabled: disabled,
    onClick: onClick,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => {
      setHover(false);
      setPress(false);
    },
    onMouseDown: () => setPress(true),
    onMouseUp: () => setPress(false),
    style: {
      display: "inline-flex",
      alignItems: "center",
      justifyContent: "center",
      gap: s.gap,
      height: s.height,
      padding: s.padding,
      font: s.font,
      letterSpacing: "var(--tracking-snug)",
      whiteSpace: "nowrap",
      borderRadius: pill ? "var(--radius-pill)" : "var(--radius-control)",
      cursor: disabled ? "not-allowed" : "pointer",
      transition: "var(--transition-control)",
      width: fullWidth ? "100%" : undefined,
      opacity: disabled ? 0.42 : 1,
      transform: press && !disabled ? "scale(.975)" : "scale(1)",
      ...v,
      ...(hover && !disabled ? HOVER[variant] : null),
      ...style
    }
  }, rest), icon ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: s.icon
  }) : null, children, iconRight ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: iconRight,
    size: s.icon
  }) : null);
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Button.jsx", error: String((e && e.message) || e) }); }

// components/core/IconButton.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const SIZES = {
  sm: 28,
  md: 32,
  lg: 40
};
function IconButton({
  icon,
  label,
  size = "md",
  variant = "plain",
  active = false,
  badge = false,
  disabled = false,
  onClick,
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const px = SIZES[size] || SIZES.md;
  const base = {
    plain: {
      background: "var(--white)",
      color: "var(--gray-600)",
      border: "1px solid var(--border-subtle)"
    },
    bare: {
      background: "transparent",
      color: "var(--gray-500)",
      border: "1px solid transparent"
    },
    inverse: {
      background: "var(--ink-900)",
      color: "var(--white)",
      border: "1px solid var(--ink-900)"
    },
    brand: {
      background: "var(--surface-brand)",
      color: "var(--white)",
      border: "1px solid var(--surface-brand)"
    }
  }[variant];
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    "aria-label": label,
    title: label,
    disabled: disabled,
    onClick: onClick,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      position: "relative",
      width: px,
      height: px,
      display: "inline-grid",
      placeItems: "center",
      borderRadius: "var(--radius-pill)",
      cursor: disabled ? "not-allowed" : "pointer",
      transition: "var(--transition-control)",
      opacity: disabled ? 0.42 : 1,
      ...base,
      ...(active ? {
        background: "var(--surface-brand-soft)",
        color: "var(--blue-700)",
        borderColor: "transparent"
      } : null),
      ...(hover && !disabled && !active ? {
        background: variant === "inverse" ? "var(--ink-800)" : variant === "brand" ? "var(--blue-500)" : "var(--surface-hover)",
        color: variant === "inverse" || variant === "brand" ? undefined : "var(--ink-900)"
      } : null),
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: Math.round(px * 0.5)
  }), badge ? /*#__PURE__*/React.createElement("span", {
    style: {
      position: "absolute",
      top: 4,
      right: 4,
      width: 6,
      height: 6,
      borderRadius: "var(--radius-pill)",
      background: "var(--red-600)",
      boxShadow: "0 0 0 2px var(--white)"
    }
  }) : null);
}
Object.assign(__ds_scope, { IconButton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/IconButton.jsx", error: String((e && e.message) || e) }); }

// components/core/ProgressBar.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function ProgressBar({
  value = 0,
  max = 100,
  tone = "brand",
  height = 8,
  label,
  valueLabel,
  segments,
  style,
  ...rest
}) {
  const pct = Math.max(0, Math.min(100, value / max * 100));
  const fill = tone === "ink" ? "var(--ink-900)" : tone === "danger" ? "var(--red-600)" : tone === "accent" ? "var(--green-500)" : "var(--surface-brand)";
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 6,
      ...style
    }
  }, rest), label || valueLabel ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      justifyContent: "space-between",
      alignItems: "baseline",
      gap: 8
    }
  }, label ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, label) : /*#__PURE__*/React.createElement("span", null), valueLabel ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, valueLabel) : null) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      gap: 4,
      height,
      borderRadius: "var(--radius-pill)",
      background: "var(--gray-150)",
      overflow: "hidden"
    }
  }, segments ? segments.map((seg, i) => /*#__PURE__*/React.createElement("span", {
    key: i,
    style: {
      width: `${seg.value / max * 100}%`,
      background: seg.color || fill,
      borderRadius: "var(--radius-pill)",
      transition: `width var(--duration-slow) var(--ease-out)`
    }
  })) : /*#__PURE__*/React.createElement("span", {
    style: {
      width: `${pct}%`,
      background: fill,
      borderRadius: "var(--radius-pill)",
      transition: `width var(--duration-slow) var(--ease-out)`
    }
  })));
}
Object.assign(__ds_scope, { ProgressBar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/ProgressBar.jsx", error: String((e && e.message) || e) }); }

// components/core/Switch.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Switch({
  checked = false,
  onChange,
  label,
  description,
  disabled = false,
  size = "md",
  style,
  ...rest
}) {
  const w = size === "sm" ? 34 : 42;
  const h = size === "sm" ? 20 : 24;
  const knob = h - 6;
  const control = /*#__PURE__*/React.createElement("button", {
    type: "button",
    role: "switch",
    "aria-checked": checked,
    disabled: disabled,
    onClick: () => onChange && onChange(!checked),
    style: {
      position: "relative",
      flex: "none",
      width: w,
      height: h,
      padding: 0,
      cursor: disabled ? "not-allowed" : "pointer",
      borderRadius: "var(--radius-pill)",
      border: "none",
      opacity: disabled ? 0.45 : 1,
      background: checked ? "var(--blue-600)" : "var(--gray-300)",
      transition: "background-color var(--duration-normal) var(--ease-standard)"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: "absolute",
      top: 3,
      left: checked ? w - knob - 3 : 3,
      width: knob,
      height: knob,
      borderRadius: "var(--radius-pill)",
      background: "var(--white)",
      boxShadow: "var(--shadow-sm)",
      transition: "left var(--duration-normal) var(--ease-spring)"
    }
  }));
  if (!label) return /*#__PURE__*/React.createElement("span", _extends({
    style: style
  }, rest), control);
  return /*#__PURE__*/React.createElement("label", _extends({
    style: {
      display: "flex",
      alignItems: description ? "flex-start" : "center",
      gap: 10,
      cursor: disabled ? "not-allowed" : "pointer",
      ...style
    }
  }, rest), control, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      gap: 2
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-medium) var(--text-sm)/1.3 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, label), description ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, description) : null));
}
Object.assign(__ds_scope, { Switch });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Switch.jsx", error: String((e && e.message) || e) }); }

// components/core/Tabs.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Tabs({
  items = [],
  value,
  onChange,
  variant = "underline",
  size = "md",
  style,
  ...rest
}) {
  const active = value != null ? value : items[0] && (items[0].id || items[0]);
  const norm = items.map(it => typeof it === "string" ? {
    id: it,
    label: it
  } : it);
  const isPill = variant === "pill";
  return /*#__PURE__*/React.createElement("div", _extends({
    role: "tablist",
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: isPill ? 4 : 18,
      padding: isPill ? 3 : 0,
      background: isPill ? "var(--gray-100)" : "transparent",
      borderRadius: isPill ? "var(--radius-pill)" : 0,
      borderBottom: isPill ? "none" : "1px solid var(--border-subtle)",
      ...style
    }
  }, rest), norm.map(it => {
    const on = it.id === active;
    return /*#__PURE__*/React.createElement("button", {
      key: it.id,
      role: "tab",
      "aria-selected": on,
      onClick: () => onChange && onChange(it.id),
      style: {
        position: "relative",
        display: "inline-flex",
        alignItems: "center",
        gap: 6,
        border: "none",
        cursor: "pointer",
        background: isPill ? on ? "var(--white)" : "transparent" : "transparent",
        padding: isPill ? "0 14px" : "0 0 9px",
        height: isPill ? size === "sm" ? 26 : 30 : "auto",
        borderRadius: isPill ? "var(--radius-pill)" : 0,
        boxShadow: isPill && on ? "var(--shadow-xs)" : "none",
        font: `${on ? "var(--weight-semibold)" : "var(--weight-medium)"} ${size === "sm" ? "var(--text-xs)" : "var(--text-sm)"}/1 var(--font-ui)`,
        color: on ? "var(--ink-900)" : "var(--text-muted)",
        transition: "var(--transition-control)"
      }
    }, it.label, it.meta ? /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, it.meta) : null, !isPill && on ? /*#__PURE__*/React.createElement("span", {
      style: {
        position: "absolute",
        left: 0,
        right: 0,
        bottom: -1,
        height: 2,
        borderRadius: "var(--radius-pill)",
        background: "var(--blue-600)"
      }
    }) : null);
  }));
}
Object.assign(__ds_scope, { Tabs });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Tabs.jsx", error: String((e && e.message) || e) }); }

// components/data/AreaChart.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function path(points, w, h, smooth = 0.22) {
  const max = Math.max(...points),
    min = Math.min(...points);
  const span = max - min || 1;
  const pts = points.map((p, i) => [i / (points.length - 1) * w, h - (p - min) / span * h]);
  let d = `M ${pts[0][0]},${pts[0][1]}`;
  for (let i = 1; i < pts.length; i++) {
    const [x0, y0] = pts[i - 1],
      [x1, y1] = pts[i];
    const dx = (x1 - x0) * smooth;
    d += ` C ${x0 + dx},${y0} ${x1 - dx},${y1} ${x1},${y1}`;
  }
  return {
    d,
    pts
  };
}
function AreaChart({
  series = [],
  height = 180,
  yTicks = [],
  xLabels = [],
  marker,
  fill = true,
  style,
  ...rest
}) {
  const W = 600;
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "flex",
      gap: 10,
      minWidth: 0,
      ...style
    }
  }, rest), yTicks.length ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      flexDirection: "column",
      justifyContent: "space-between",
      height,
      font: "var(--type-caption)",
      color: "var(--chart-axis)",
      flex: "none",
      textAlign: "right"
    }
  }, yTicks.map(t => /*#__PURE__*/React.createElement("span", {
    key: t
  }, t))) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("svg", {
    viewBox: `0 0 ${W} ${height}`,
    preserveAspectRatio: "none",
    style: {
      width: "100%",
      height,
      display: "block",
      overflow: "visible"
    }
  }, /*#__PURE__*/React.createElement("defs", null, /*#__PURE__*/React.createElement("linearGradient", {
    id: "fxArea",
    x1: "0",
    y1: "0",
    x2: "0",
    y2: "1"
  }, /*#__PURE__*/React.createElement("stop", {
    offset: "0%",
    stopColor: "var(--chart-1)",
    stopOpacity: "0.26"
  }), /*#__PURE__*/React.createElement("stop", {
    offset: "100%",
    stopColor: "var(--chart-1)",
    stopOpacity: "0"
  }))), yTicks.map((t, i) => /*#__PURE__*/React.createElement("line", {
    key: i,
    x1: "0",
    x2: W,
    y1: i * height / Math.max(1, yTicks.length - 1),
    y2: i * height / Math.max(1, yTicks.length - 1),
    stroke: "var(--chart-grid)",
    strokeWidth: "1",
    vectorEffect: "non-scaling-stroke"
  })), series.map((s, si) => {
    const {
      d,
      pts
    } = path(s.points, W, height - 10);
    return /*#__PURE__*/React.createElement("g", {
      key: si
    }, fill && s.fill !== false ? /*#__PURE__*/React.createElement("path", {
      d: `${d} L ${W},${height} L 0,${height} Z`,
      fill: "url(#fxArea)"
    }) : null, /*#__PURE__*/React.createElement("path", {
      d: d,
      fill: "none",
      stroke: s.color || "var(--chart-1)",
      strokeWidth: s.width || 2,
      vectorEffect: "non-scaling-stroke",
      strokeLinecap: "round"
    }), marker != null && si === 0 ? /*#__PURE__*/React.createElement("g", null, /*#__PURE__*/React.createElement("line", {
      x1: pts[marker][0],
      x2: pts[marker][0],
      y1: pts[marker][1],
      y2: height,
      stroke: "var(--chart-1)",
      strokeWidth: "1",
      vectorEffect: "non-scaling-stroke",
      strokeDasharray: "3 3"
    }), /*#__PURE__*/React.createElement("circle", {
      cx: pts[marker][0],
      cy: pts[marker][1],
      r: "4.5",
      fill: "var(--chart-1)",
      stroke: "var(--white)",
      strokeWidth: "2"
    })) : null);
  })), xLabels.length ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      marginTop: 6
    }
  }, xLabels.map((l, i) => /*#__PURE__*/React.createElement("span", {
    key: i,
    style: {
      flex: 1,
      textAlign: "center",
      font: "var(--type-caption)",
      color: "var(--chart-axis)"
    }
  }, l))) : null));
}
function Sparkline({
  points = [],
  width = 96,
  height = 28,
  color = "var(--chart-1)",
  style
}) {
  const {
    d
  } = path(points, width, height - 4);
  return /*#__PURE__*/React.createElement("svg", {
    width: width,
    height: height,
    style: {
      display: "block",
      ...style
    }
  }, /*#__PURE__*/React.createElement("path", {
    d: d,
    fill: "none",
    stroke: color,
    strokeWidth: "1.75",
    strokeLinecap: "round"
  }));
}
Object.assign(__ds_scope, { AreaChart, Sparkline });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/AreaChart.jsx", error: String((e && e.message) || e) }); }

// components/data/BarChart.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/* Cashflow bars: income above the axis (ink), expense below (green) — as in the source. */
function BarChart({
  data = [],
  height = 200,
  gap = 0.34,
  showAxis = true,
  formatTick = v => v,
  positiveColor = "var(--chart-1)",
  negativeColor = "var(--chart-2)",
  style,
  ...rest
}) {
  const max = Math.max(1, ...data.map(d => Math.max(Math.abs(d.income || 0), Math.abs(d.expense || 0))));
  const step = 100 / Math.max(1, data.length);
  const bw = step * (1 - gap);
  const ticks = [max, max / 2, 0, -max / 2, -max];
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "flex",
      gap: 10,
      ...style
    }
  }, rest), showAxis ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      flexDirection: "column",
      justifyContent: "space-between",
      height,
      font: "var(--type-caption)",
      color: "var(--chart-axis)",
      textAlign: "right",
      flex: "none"
    }
  }, ticks.map((t, i) => /*#__PURE__*/React.createElement("span", {
    key: i
  }, formatTick(Math.round(t))))) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      position: "relative",
      flex: 1,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("svg", {
    viewBox: `0 0 100 ${height}`,
    preserveAspectRatio: "none",
    style: {
      width: "100%",
      height,
      display: "block",
      overflow: "visible"
    }
  }, ticks.map((t, i) => /*#__PURE__*/React.createElement("line", {
    key: i,
    x1: "0",
    x2: "100",
    y1: i * height / (ticks.length - 1),
    y2: i * height / (ticks.length - 1),
    stroke: "var(--chart-grid)",
    strokeWidth: "1",
    vectorEffect: "non-scaling-stroke",
    strokeDasharray: i === 2 ? "0" : "3 4"
  })), data.map((d, i) => {
    const x = i * step + (step - bw) / 2;
    const mid = height / 2;
    const ih = (d.income || 0) / max * (height / 2);
    const eh = (d.expense || 0) / max * (height / 2);
    return /*#__PURE__*/React.createElement("g", {
      key: i
    }, /*#__PURE__*/React.createElement("rect", {
      x: x,
      y: mid - ih,
      width: bw,
      height: Math.max(2, ih),
      rx: "1.6",
      fill: positiveColor
    }), /*#__PURE__*/React.createElement("rect", {
      x: x,
      y: mid,
      width: bw,
      height: Math.max(2, eh),
      rx: "1.6",
      fill: negativeColor
    }));
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      marginTop: 8
    }
  }, data.map((d, i) => /*#__PURE__*/React.createElement("span", {
    key: i,
    style: {
      flex: 1,
      textAlign: "center",
      font: "var(--type-caption)",
      color: "var(--chart-axis)"
    }
  }, d.label)))));
}
function ChartLegend({
  items = [],
  style
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 14,
      ...style
    }
  }, items.map(it => /*#__PURE__*/React.createElement("span", {
    key: it.label,
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 6,
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 8,
      height: 8,
      borderRadius: 2,
      background: it.color
    }
  }), it.label)));
}
Object.assign(__ds_scope, { BarChart, ChartLegend });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/BarChart.jsx", error: String((e && e.message) || e) }); }

// components/data/DonutChart.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function DonutChart({
  segments = [],
  size = 168,
  thickness = 22,
  label = "Total",
  value,
  gapDeg = 3,
  style,
  ...rest
}) {
  const total = segments.reduce((s, x) => s + x.value, 0) || 1;
  const r = (size - thickness) / 2;
  const c = 2 * Math.PI * r;
  let offset = 0;
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      position: "relative",
      width: size,
      height: size,
      flex: "none",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("svg", {
    width: size,
    height: size,
    style: {
      transform: "rotate(-90deg)"
    }
  }, /*#__PURE__*/React.createElement("circle", {
    cx: size / 2,
    cy: size / 2,
    r: r,
    fill: "none",
    stroke: "var(--gray-150)",
    strokeWidth: thickness
  }), segments.map((s, i) => {
    const frac = s.value / total;
    const len = Math.max(0, frac * c - gapDeg / 360 * c);
    const dash = `${len} ${c - len}`;
    const el = /*#__PURE__*/React.createElement("circle", {
      key: i,
      cx: size / 2,
      cy: size / 2,
      r: r,
      fill: "none",
      stroke: s.color,
      strokeWidth: thickness,
      strokeDasharray: dash,
      strokeDashoffset: -offset,
      strokeLinecap: "round"
    });
    offset += frac * c;
    return el;
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      position: "absolute",
      inset: 0,
      display: "grid",
      placeItems: "center",
      textAlign: "center",
      gap: 2
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      gap: 2
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, label), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)",
      color: "var(--ink-900)",
      fontVariantNumeric: "tabular-nums"
    }
  }, value))));
}
function CategoryLegend({
  items = [],
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 10,
      minWidth: 0,
      ...style
    }
  }, rest), items.map(it => /*#__PURE__*/React.createElement("div", {
    key: it.label,
    style: {
      display: "flex",
      alignItems: "center",
      gap: 10,
      minWidth: 0
    }
  }, it.pct != null ? /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      width: 30,
      height: 20,
      flex: "none",
      borderRadius: "var(--radius-xs)",
      background: it.color,
      font: "var(--weight-semibold) var(--text-2xs)/1 var(--font-ui)",
      color: it.pctColor || "var(--ink-900)"
    }
  }, it.pct) : /*#__PURE__*/React.createElement("span", {
    style: {
      width: 8,
      height: 8,
      flex: "none",
      borderRadius: "var(--radius-pill)",
      background: it.color
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      flex: 1,
      font: "var(--type-body-sm)",
      color: "var(--gray-600)",
      overflow: "hidden",
      textOverflow: "ellipsis",
      whiteSpace: "nowrap"
    }
  }, it.label), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)",
      color: "var(--ink-900)",
      fontVariantNumeric: "tabular-nums",
      flex: "none"
    }
  }, it.value))));
}
Object.assign(__ds_scope, { DonutChart, CategoryLegend });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/DonutChart.jsx", error: String((e && e.message) || e) }); }

// components/finance/AssistantComposer.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function AssistantOrb({
  size = 72,
  style
}) {
  return /*#__PURE__*/React.createElement("span", {
    style: {
      display: "block",
      width: size,
      height: size,
      borderRadius: "var(--radius-pill)",
      background: "var(--gradient-orb)",
      filter: "blur(.2px)",
      boxShadow: "var(--shadow-brand)",
      ...style
    }
  });
}
function AssistantComposer({
  placeholder = "Ask anything...",
  value,
  onChange,
  onSend,
  chips = [],
  models = ["Finance", "Choose Model"],
  compact = false,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 10,
      padding: 12,
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-lg)",
      boxShadow: "var(--shadow-sm)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "wand-sparkles",
    size: 15,
    color: "var(--gray-400)"
  }), /*#__PURE__*/React.createElement("input", {
    value: value,
    onChange: onChange,
    placeholder: placeholder,
    style: {
      flex: 1,
      minWidth: 0,
      border: "none",
      outline: "none",
      background: "transparent",
      font: "var(--type-body-sm)",
      color: "var(--ink-900)"
    }
  })), chips.length ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      flexWrap: "wrap",
      gap: 6
    }
  }, chips.map(c => /*#__PURE__*/React.createElement("span", {
    key: c,
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 5,
      height: 24,
      padding: "0 10px",
      background: "var(--gray-50)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-pill)",
      font: "var(--type-caption)",
      color: "var(--gray-600)"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "sparkles",
    size: 11,
    color: "var(--blue-600)"
  }), c))) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      justifyContent: "space-between",
      gap: 8
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 6
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "paperclip",
    label: "Attach",
    size: "sm",
    variant: "bare"
  }), /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "mic",
    label: "Voice",
    size: "sm",
    variant: "bare"
  }), /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "image",
    label: "Image",
    size: "sm",
    variant: "bare"
  }), !compact ? models.map(m => /*#__PURE__*/React.createElement("span", {
    key: m,
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 4,
      height: 24,
      padding: "0 9px",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-pill)",
      font: "var(--type-caption)",
      color: "var(--gray-600)"
    }
  }, m, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "chevron-down",
    size: 11,
    color: "var(--gray-400)"
  }))) : null), /*#__PURE__*/React.createElement(__ds_scope.Button, {
    variant: "brand",
    size: "sm",
    icon: "arrow-up",
    onClick: onSend
  }, "Send")));
}
Object.assign(__ds_scope, { AssistantOrb, AssistantComposer });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/finance/AssistantComposer.jsx", error: String((e && e.message) || e) }); }

// components/finance/CreditCardItem.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function CreditCardItem({
  label = "Personal",
  brand = "VISA",
  last4 = "8744",
  selected = false,
  tone = "blue",
  onSelect,
  onMenu,
  style,
  ...rest
}) {
  const faces = {
    blue: "linear-gradient(135deg,#4A63E8 0%,#0229C4 100%)",
    gray: "linear-gradient(135deg,#D8D8D8 0%,#B4B4B4 100%)",
    ink: "linear-gradient(135deg,#0A2469 0%,#05153F 100%)"
  };
  return /*#__PURE__*/React.createElement("div", _extends({
    onClick: onSelect,
    style: {
      display: "flex",
      alignItems: "center",
      gap: 10,
      padding: "8px 10px",
      cursor: "pointer",
      background: selected ? "var(--blue-50)" : "var(--white)",
      border: `1px solid ${selected ? "var(--blue-200)" : "var(--border-subtle)"}`,
      borderRadius: "var(--radius-md)",
      transition: "var(--transition-control)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      flex: "none",
      width: 15,
      height: 15,
      borderRadius: "var(--radius-pill)",
      background: "var(--white)",
      border: `1.5px solid ${selected ? "var(--blue-600)" : "var(--border-strong)"}`
    }
  }, selected ? /*#__PURE__*/React.createElement("span", {
    style: {
      width: 7,
      height: 7,
      borderRadius: "var(--radius-pill)",
      background: "var(--blue-600)"
    }
  }) : null), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "start",
      width: 36,
      height: 24,
      flex: "none",
      padding: 4,
      borderRadius: "var(--radius-xs)",
      background: faces[tone]
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-bold) 6px/1 var(--font-ui)",
      color: tone === "gray" ? "var(--ink-900)" : "var(--white)",
      letterSpacing: "0.06em"
    }
  }, brand)), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      gap: 1,
      flex: 1,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, label), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)",
      fontFamily: "var(--font-accent)"
    }
  }, "\u2022\u2022\u2022\u2022 ", last4)), /*#__PURE__*/React.createElement("button", {
    type: "button",
    "aria-label": "Card options",
    onClick: e => {
      e.stopPropagation();
      onMenu && onMenu();
    },
    style: {
      border: "none",
      background: "transparent",
      cursor: "pointer",
      padding: 4,
      display: "grid",
      placeItems: "center"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "more-horizontal",
    size: 15,
    color: "var(--gray-400)"
  })));
}
function ActionTile({
  icon = "plus",
  label,
  onClick,
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    onClick: onClick,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: "grid",
      justifyItems: "center",
      gap: 5,
      padding: "10px 8px",
      minWidth: 64,
      background: hover ? "var(--surface-hover)" : "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-md)",
      cursor: "pointer",
      transition: "var(--transition-control)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      width: 26,
      height: 26,
      borderRadius: "var(--radius-pill)",
      background: "var(--gray-50)"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 14,
    color: "var(--ink-900)"
  })), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--gray-600)"
    }
  }, label));
}
Object.assign(__ds_scope, { CreditCardItem, ActionTile });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/finance/CreditCardItem.jsx", error: String((e && e.message) || e) }); }

// components/finance/ExchangePanel.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function FlagSelect({
  flag,
  code,
  onClick
}) {
  return /*#__PURE__*/React.createElement("button", {
    type: "button",
    onClick: onClick,
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 6,
      height: 30,
      padding: "0 8px",
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-pill)",
      cursor: "pointer",
      font: "var(--weight-medium) var(--text-xs)/1 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 13
    }
  }, flag), code, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "chevron-down",
    size: 13,
    color: "var(--gray-400)"
  }));
}
function ExchangePanel({
  from = {
    flag: "\u{1F1FA}\u{1F1F8}",
    code: "USD"
  },
  to = {
    flag: "\u{1F1EC}\u{1F1E7}",
    code: "GBP"
  },
  rate = "1 USD = 0.77 GBP",
  amount = "$100.00",
  available = "$1600.86",
  rows = [{
    label: "Tax (2%)",
    value: "$2.00"
  }, {
    label: "Exchange fee (1%)",
    value: "$1.00"
  }, {
    label: "Total amount",
    value: "\u20AC90.7"
  }],
  cta = "Exchange",
  onExchange,
  onSwap,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 12,
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "grid",
      gap: 10,
      padding: 12,
      background: "var(--surface-sunken)",
      borderRadius: "var(--radius-md)",
      border: "1px solid var(--border-subtle)",
      justifyItems: "center"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, rate), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(FlagSelect, from), /*#__PURE__*/React.createElement("button", {
    type: "button",
    "aria-label": "Swap currencies",
    onClick: onSwap,
    style: {
      display: "grid",
      placeItems: "center",
      width: 30,
      height: 30,
      borderRadius: "var(--radius-pill)",
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      cursor: "pointer"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "arrow-left-right",
    size: 14,
    color: "var(--blue-600)"
  })), /*#__PURE__*/React.createElement(FlagSelect, to)), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "grid",
      gap: 2,
      justifyItems: "center"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)",
      color: "var(--ink-900)",
      fontVariantNumeric: "tabular-nums"
    }
  }, amount), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, "Available: ", available))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      gap: 8
    }
  }, rows.map(r => /*#__PURE__*/React.createElement("div", {
    key: r.label,
    style: {
      flex: 1,
      display: "grid",
      gap: 3,
      padding: "8px 10px",
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-sm)"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, r.label), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)",
      color: "var(--ink-900)",
      fontVariantNumeric: "tabular-nums"
    }
  }, r.value)))), /*#__PURE__*/React.createElement(__ds_scope.Button, {
    variant: "brand",
    fullWidth: true,
    pill: false,
    onClick: onExchange
  }, cta));
}
Object.assign(__ds_scope, { ExchangePanel });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/finance/ExchangePanel.jsx", error: String((e && e.message) || e) }); }

// components/finance/SuggestionCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function SuggestionCard({
  title,
  body,
  cta = "Learn more",
  onClick,
  icon,
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  return /*#__PURE__*/React.createElement("div", _extends({
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: "grid",
      gap: 6,
      padding: 14,
      cursor: "pointer",
      background: "var(--white)",
      border: `1px solid ${hover ? "var(--blue-300)" : "var(--border-subtle)"}`,
      borderRadius: "var(--radius-md)",
      boxShadow: hover ? "var(--shadow-sm)" : "none",
      transition: "var(--transition-control)",
      ...style
    },
    onClick: onClick
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 7
    }
  }, icon ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 14,
    color: "var(--blue-600)"
  }) : null, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, title)), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, body), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 5,
      marginTop: 2,
      font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, cta, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "arrow-right",
    size: 12,
    style: {
      transform: hover ? "translateX(2px)" : "none",
      transition: "transform var(--duration-fast) var(--ease-standard)"
    }
  })));
}
Object.assign(__ds_scope, { SuggestionCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/finance/SuggestionCard.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Checkbox({
  checked = false,
  indeterminate = false,
  onChange,
  label,
  disabled = false,
  style,
  ...rest
}) {
  const on = checked || indeterminate;
  const box = /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      flex: "none",
      width: 16,
      height: 16,
      borderRadius: "var(--radius-xs)",
      background: on ? "var(--ink-900)" : "var(--white)",
      border: `1px solid ${on ? "var(--ink-900)" : "var(--border-strong)"}`,
      transition: "var(--transition-control)"
    }
  }, indeterminate ? /*#__PURE__*/React.createElement("span", {
    style: {
      width: 8,
      height: 1.5,
      background: "var(--white)",
      borderRadius: 1
    }
  }) : checked ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "check",
    size: 11,
    color: "var(--white)"
  }) : null);
  return /*#__PURE__*/React.createElement("label", _extends({
    onClick: e => {
      if (disabled) return;
      e.preventDefault();
      onChange && onChange(!checked);
    },
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 8,
      cursor: disabled ? "not-allowed" : "pointer",
      opacity: disabled ? 0.45 : 1,
      ...style
    }
  }, rest), box, label ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-body-sm)",
      color: "var(--ink-900)"
    }
  }, label) : null);
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/data/DataTable.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function DataTable({
  columns = [],
  rows = [],
  selectable = false,
  selected = [],
  onSelect,
  dense = false,
  emptyLabel = "Nothing here yet",
  style,
  ...rest
}) {
  const rowH = dense ? 44 : "var(--row-height)";
  const cell = {
    padding: "0 12px",
    verticalAlign: "middle",
    borderBottom: "1px solid var(--border-subtle)"
  };
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      width: "100%",
      overflowX: "auto",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("table", {
    style: {
      width: "100%",
      borderCollapse: "collapse",
      tableLayout: "auto"
    }
  }, /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", {
    style: {
      height: 34,
      background: "var(--surface-sunken)"
    }
  }, selectable ? /*#__PURE__*/React.createElement("th", {
    style: {
      ...cell,
      width: 36,
      borderBottomColor: "var(--border-default)"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Checkbox, {
    checked: selected.length === rows.length && rows.length > 0,
    indeterminate: selected.length > 0 && selected.length < rows.length,
    onChange: () => onSelect && onSelect(selected.length === rows.length ? [] : rows.map((_, i) => i))
  })) : null, columns.map(c => /*#__PURE__*/React.createElement("th", {
    key: c.key,
    style: {
      ...cell,
      textAlign: c.align || "left",
      borderBottomColor: "var(--border-default)",
      font: "var(--weight-medium) var(--text-2xs)/1 var(--font-ui)",
      color: "var(--text-subtle)",
      whiteSpace: "nowrap",
      width: c.width
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 4,
      justifyContent: c.align === "right" ? "flex-end" : "flex-start"
    }
  }, c.label, c.sortable ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "chevrons-up-down",
    size: 11,
    color: "var(--gray-300)"
  }) : null))))), /*#__PURE__*/React.createElement("tbody", null, rows.length === 0 ? /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("td", {
    colSpan: columns.length + (selectable ? 1 : 0),
    style: {
      ...cell,
      height: 88,
      textAlign: "center",
      font: "var(--type-body-sm)",
      color: "var(--text-subtle)"
    }
  }, emptyLabel)) : rows.map((row, i) => /*#__PURE__*/React.createElement("tr", {
    key: i,
    style: {
      height: rowH,
      transition: "background-color var(--duration-fast) var(--ease-standard)"
    },
    onMouseEnter: e => {
      e.currentTarget.style.background = "var(--surface-hover)";
    },
    onMouseLeave: e => {
      e.currentTarget.style.background = "transparent";
    }
  }, selectable ? /*#__PURE__*/React.createElement("td", {
    style: cell
  }, /*#__PURE__*/React.createElement(__ds_scope.Checkbox, {
    checked: selected.includes(i),
    onChange: () => onSelect && onSelect(selected.includes(i) ? selected.filter(x => x !== i) : [...selected, i])
  })) : null, columns.map(c => /*#__PURE__*/React.createElement("td", {
    key: c.key,
    style: {
      ...cell,
      textAlign: c.align || "left",
      font: "var(--type-body-sm)",
      color: "var(--ink-900)"
    }
  }, c.render ? c.render(row) : row[c.key])))))));
}
function TransactionCell({
  icon = "receipt",
  title,
  meta,
  tone = "neutral"
}) {
  const bg = {
    income: "var(--green-100)",
    expense: "var(--red-100)",
    neutral: "var(--gray-100)"
  }[tone];
  const fg = {
    income: "var(--status-success-fg)",
    expense: "var(--red-600)",
    neutral: "var(--gray-600)"
  }[tone];
  return /*#__PURE__*/React.createElement("span", {
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 10,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      width: 28,
      height: 28,
      flex: "none",
      borderRadius: "var(--radius-pill)",
      background: bg
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 14,
    color: fg
  })), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      gap: 1,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-medium) var(--text-sm)/1.2 var(--font-ui)",
      color: "var(--ink-900)",
      overflow: "hidden",
      textOverflow: "ellipsis",
      whiteSpace: "nowrap"
    }
  }, title), meta ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, meta) : null));
}
function Amount({
  value,
  positive
}) {
  const pos = positive != null ? positive : !String(value).trim().startsWith("-");
  return /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)",
      fontVariantNumeric: "tabular-nums",
      color: pos ? "var(--ink-900)" : "var(--red-600)"
    }
  }, value);
}
Object.assign(__ds_scope, { DataTable, TransactionCell, Amount });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/DataTable.jsx", error: String((e && e.message) || e) }); }

// components/forms/Input.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Input({
  label,
  hint,
  error,
  icon,
  suffix,
  size = "md",
  value,
  onChange,
  placeholder,
  type = "text",
  disabled = false,
  style,
  inputStyle,
  ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const h = size === "sm" ? "var(--control-height-sm)" : size === "lg" ? "var(--control-height-lg)" : "var(--control-height)";
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: "grid",
      gap: 6,
      ...style
    }
  }, label ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-label)",
      color: "var(--text-muted)"
    }
  }, label) : null, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 8,
      height: h,
      padding: "0 12px",
      background: disabled ? "var(--gray-50)" : "var(--white)",
      border: `1px solid ${error ? "var(--red-600)" : focus ? "var(--border-brand)" : "var(--border-default)"}`,
      borderRadius: "var(--radius-control)",
      boxShadow: focus ? "var(--ring-focus)" : "none",
      transition: "var(--transition-control)"
    }
  }, icon ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 15,
    color: "var(--gray-400)"
  }) : null, /*#__PURE__*/React.createElement("input", _extends({
    type: type,
    value: value,
    onChange: onChange,
    placeholder: placeholder,
    disabled: disabled,
    onFocus: () => setFocus(true),
    onBlur: () => setFocus(false),
    style: {
      flex: 1,
      minWidth: 0,
      border: "none",
      outline: "none",
      background: "transparent",
      font: "var(--type-body-sm)",
      color: "var(--ink-900)",
      ...inputStyle
    }
  }, rest)), suffix ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, suffix) : null), error || hint ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: error ? "var(--red-600)" : "var(--text-muted)"
    }
  }, error || hint) : null);
}
Object.assign(__ds_scope, { Input });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Input.jsx", error: String((e && e.message) || e) }); }

// components/forms/Radio.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Radio({
  checked = false,
  onChange,
  label,
  description,
  disabled = false,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("label", _extends({
    onClick: () => {
      if (!disabled && onChange) onChange(true);
    },
    style: {
      display: "inline-flex",
      alignItems: description ? "flex-start" : "center",
      gap: 10,
      cursor: disabled ? "not-allowed" : "pointer",
      opacity: disabled ? 0.45 : 1,
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      flex: "none",
      width: 16,
      height: 16,
      marginTop: description ? 2 : 0,
      borderRadius: "var(--radius-pill)",
      background: "var(--white)",
      border: `1.5px solid ${checked ? "var(--blue-600)" : "var(--border-strong)"}`,
      transition: "var(--transition-control)"
    }
  }, checked ? /*#__PURE__*/React.createElement("span", {
    style: {
      width: 8,
      height: 8,
      borderRadius: "var(--radius-pill)",
      background: "var(--blue-600)"
    }
  }) : null), label ? /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      gap: 2
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-medium) var(--text-sm)/1.3 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, label), description ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, description) : null) : null);
}
Object.assign(__ds_scope, { Radio });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Radio.jsx", error: String((e && e.message) || e) }); }

// components/forms/SearchField.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function SearchField({
  placeholder = "Search anything",
  value,
  onChange,
  shortcut = ["\u2318", "F"],
  width = 300,
  size = "md",
  style,
  ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const h = size === "sm" ? "var(--control-height-sm)" : "var(--control-height)";
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "flex",
      alignItems: "center",
      gap: 8,
      width,
      height: h,
      padding: "0 6px 0 12px",
      background: "var(--white)",
      borderRadius: "var(--radius-pill)",
      border: `1px solid ${focus ? "var(--border-brand)" : "var(--border-subtle)"}`,
      boxShadow: focus ? "var(--ring-focus)" : "var(--shadow-xs)",
      transition: "var(--transition-control)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "search",
    size: 15,
    color: "var(--gray-400)"
  }), /*#__PURE__*/React.createElement("input", {
    value: value,
    onChange: onChange,
    placeholder: placeholder,
    onFocus: () => setFocus(true),
    onBlur: () => setFocus(false),
    style: {
      flex: 1,
      minWidth: 0,
      border: "none",
      outline: "none",
      background: "transparent",
      font: "var(--type-body-sm)",
      color: "var(--ink-900)"
    }
  }), shortcut ? /*#__PURE__*/React.createElement("span", {
    style: {
      display: "flex",
      gap: 3
    }
  }, shortcut.map(k => /*#__PURE__*/React.createElement("kbd", {
    key: k,
    style: {
      display: "grid",
      placeItems: "center",
      minWidth: 20,
      height: 20,
      padding: "0 4px",
      background: "var(--gray-50)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-xs)",
      font: "var(--weight-medium) var(--text-2xs)/1 var(--font-accent)",
      color: "var(--gray-500)"
    }
  }, k))) : null);
}
Object.assign(__ds_scope, { SearchField });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/SearchField.jsx", error: String((e && e.message) || e) }); }

// components/forms/Select.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Select({
  options = [],
  value,
  onChange,
  label,
  size = "sm",
  variant = "quiet",
  leading,
  width,
  disabled = false,
  style,
  ...rest
}) {
  const [open, setOpen] = React.useState(false);
  const norm = options.map(o => typeof o === "string" ? {
    value: o,
    label: o
  } : o);
  const current = norm.find(o => o.value === value) || norm[0] || {
    label: ""
  };
  const h = size === "sm" ? "var(--control-height-sm)" : "var(--control-height)";
  const quiet = variant === "quiet";
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      position: "relative",
      display: "inline-grid",
      gap: 6,
      width,
      ...style
    }
  }, rest), label ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-label)",
      color: "var(--text-muted)"
    }
  }, label) : null, /*#__PURE__*/React.createElement("button", {
    type: "button",
    disabled: disabled,
    onClick: () => setOpen(o => !o),
    style: {
      display: "inline-flex",
      alignItems: "center",
      justifyContent: "space-between",
      gap: 8,
      height: h,
      padding: "0 10px 0 12px",
      width: width ? "100%" : undefined,
      background: quiet ? "var(--white)" : "var(--gray-50)",
      border: `1px solid ${open ? "var(--border-brand)" : "var(--border-subtle)"}`,
      borderRadius: quiet ? "var(--radius-pill)" : "var(--radius-control)",
      font: "var(--weight-medium) var(--text-xs)/1 var(--font-ui)",
      color: "var(--ink-900)",
      cursor: disabled ? "not-allowed" : "pointer",
      transition: "var(--transition-control)",
      whiteSpace: "nowrap"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 6
    }
  }, leading, current.label), /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "chevron-down",
    size: 14,
    color: "var(--gray-400)",
    style: {
      transform: open ? "rotate(180deg)" : "none",
      transition: "transform var(--duration-fast) var(--ease-standard)"
    }
  })), open ? /*#__PURE__*/React.createElement("div", {
    style: {
      position: "absolute",
      top: "calc(100% + 6px)",
      right: 0,
      zIndex: 40,
      minWidth: "100%",
      padding: 4,
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-md)",
      boxShadow: "var(--shadow-lg)"
    }
  }, norm.map(o => /*#__PURE__*/React.createElement("button", {
    key: o.value,
    type: "button",
    onClick: () => {
      onChange && onChange(o.value);
      setOpen(false);
    },
    style: {
      display: "flex",
      alignItems: "center",
      justifyContent: "space-between",
      gap: 12,
      width: "100%",
      padding: "7px 10px",
      border: "none",
      background: o.value === current.value ? "var(--surface-brand-soft)" : "transparent",
      borderRadius: "var(--radius-sm)",
      cursor: "pointer",
      textAlign: "left",
      font: "var(--weight-medium) var(--text-xs)/1.2 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, o.label, o.value === current.value ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "check",
    size: 13,
    color: "var(--ink-900)"
  }) : null))) : null);
}
Object.assign(__ds_scope, { Select });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Select.jsx", error: String((e && e.message) || e) }); }

// components/navigation/SidebarNav.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const ASSETS = () => typeof window !== "undefined" && window.FYNIX_ASSET_BASE || "";
function AccountSwitcher({
  name = "Jenny Wilson",
  role = "Personal Account",
  avatar,
  onClick,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    onClick: onClick,
    style: {
      display: "flex",
      alignItems: "center",
      gap: 9,
      width: "100%",
      padding: "7px 10px",
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-md)",
      boxShadow: "var(--shadow-xs)",
      cursor: "pointer",
      textAlign: "left",
      transition: "var(--transition-control)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      width: 28,
      height: 28,
      flex: "none",
      borderRadius: "var(--radius-pill)",
      overflow: "hidden",
      background: "var(--gradient-orb)"
    }
  }, avatar ? /*#__PURE__*/React.createElement("img", {
    src: avatar,
    alt: "",
    style: {
      width: "100%",
      height: "100%",
      objectFit: "cover"
    }
  }) : null), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      gap: 1,
      minWidth: 0,
      flex: 1
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-xs)/1.2 var(--font-ui)",
      color: "var(--ink-900)",
      overflow: "hidden",
      textOverflow: "ellipsis",
      whiteSpace: "nowrap"
    }
  }, name), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, role)), /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "chevron-down",
    size: 14,
    color: "var(--gray-400)"
  }));
}
function SidebarNav({
  sections = [],
  active,
  onNavigate,
  header,
  footer,
  collapsed = false,
  brand = true,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("nav", _extends({
    style: {
      display: "flex",
      flexDirection: "column",
      gap: 14,
      flex: "none",
      width: collapsed ? "var(--sidebar-width-collapsed)" : "var(--sidebar-width)",
      padding: "14px 12px",
      background: "var(--surface-canvas)",
      borderRight: "1px solid var(--border-subtle)",
      overflow: "hidden",
      transition: "width var(--duration-normal) var(--ease-standard)",
      ...style
    }
  }, rest), brand ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 8,
      padding: "2px 6px 0"
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: ASSETS() + "assets/logo-mark.png",
    alt: "Fynix",
    style: {
      width: 20,
      flex: "none"
    }
  }), !collapsed ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-md)/1 var(--font-display)",
      color: "var(--ink-900)",
      letterSpacing: "var(--tracking-snug)"
    }
  }, "Fynix") : null) : null, header, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "grid",
      gap: 14,
      flex: 1,
      minHeight: 0,
      overflowY: "auto"
    }
  }, sections.map(sec => /*#__PURE__*/React.createElement("div", {
    key: sec.title || "main",
    style: {
      display: "grid",
      gap: 2
    }
  }, sec.title && !collapsed ? /*#__PURE__*/React.createElement("span", {
    style: {
      padding: "6px 8px 4px",
      font: "var(--type-eyebrow)",
      letterSpacing: "var(--tracking-caps)",
      textTransform: "uppercase",
      color: "var(--text-subtle)"
    }
  }, sec.title) : null, sec.items.map(item => {
    const on = item.id === active;
    return /*#__PURE__*/React.createElement("button", {
      key: item.id,
      type: "button",
      onClick: () => onNavigate && onNavigate(item.id),
      title: collapsed ? item.label : undefined,
      style: {
        display: "flex",
        alignItems: "center",
        gap: 10,
        height: 34,
        padding: collapsed ? 0 : "0 10px",
        justifyContent: collapsed ? "center" : "flex-start",
        background: on ? "var(--white)" : "transparent",
        boxShadow: on ? "var(--shadow-xs)" : "none",
        border: "1px solid " + (on ? "var(--border-subtle)" : "transparent"),
        borderRadius: "var(--radius-sm)",
        cursor: "pointer",
        width: "100%",
        font: `${on ? "var(--weight-semibold)" : "var(--weight-medium)"} var(--text-sm)/1 var(--font-ui)`,
        color: on ? "var(--ink-900)" : "var(--gray-600)",
        transition: "var(--transition-control)"
      },
      onMouseEnter: e => {
        if (!on) e.currentTarget.style.background = "var(--gray-150)";
      },
      onMouseLeave: e => {
        if (!on) e.currentTarget.style.background = "transparent";
      }
    }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
      name: item.icon,
      size: 16,
      color: on ? "var(--ink-900)" : "var(--gray-500)"
    }), !collapsed ? /*#__PURE__*/React.createElement("span", {
      style: {
        flex: 1,
        textAlign: "left"
      }
    }, item.label) : null, !collapsed && item.badge ? /*#__PURE__*/React.createElement("span", {
      style: {
        padding: "1px 6px",
        borderRadius: "var(--radius-pill)",
        background: "var(--surface-brand)",
        font: "var(--weight-semibold) var(--text-2xs)/1.6 var(--font-ui)",
        color: "var(--white)"
      }
    }, item.badge) : null);
  })))), footer);
}
Object.assign(__ds_scope, { AccountSwitcher, SidebarNav });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/SidebarNav.jsx", error: String((e && e.message) || e) }); }

// components/navigation/TopBar.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function TopBar({
  title,
  subtitle,
  actions,
  searchWidth = 300,
  onSearch,
  avatar,
  userName = "Jenny Wilson",
  sticky = true,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("header", _extends({
    style: {
      position: sticky ? "sticky" : "static",
      top: 0,
      zIndex: 30,
      display: "flex",
      alignItems: "center",
      gap: 16,
      minHeight: "var(--topbar-height)",
      padding: "0 16px",
      background: "var(--surface-glass)",
      backdropFilter: "var(--blur-glass)",
      WebkitBackdropFilter: "var(--blur-glass)",
      borderBottom: "1px solid var(--border-subtle)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "grid",
      gap: 1,
      minWidth: 0,
      flex: 1
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, title), subtitle ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)",
      overflow: "hidden",
      textOverflow: "ellipsis",
      whiteSpace: "nowrap"
    }
  }, "\u201C", subtitle, "\u201D") : null), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 8,
      flex: "none"
    }
  }, actions, /*#__PURE__*/React.createElement(__ds_scope.SearchField, {
    width: searchWidth,
    onChange: onSearch
  }), /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "settings",
    label: "Settings",
    variant: "bare"
  }), /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "circle-help",
    label: "Help",
    variant: "bare"
  }), /*#__PURE__*/React.createElement(__ds_scope.Avatar, {
    src: avatar,
    name: userName,
    size: "md",
    ring: true
  })));
}
Object.assign(__ds_scope, { TopBar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/TopBar.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/BalanceCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function BalanceCard({
  label = "Total Balance",
  amount,
  currency = "USD",
  onAdd,
  primaryAction = "Deposit",
  secondaryAction = "Send",
  onPrimary,
  onSecondary,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      position: "relative",
      overflow: "hidden",
      display: "flex",
      flexDirection: "column",
      justifyContent: "space-between",
      gap: 18,
      padding: 16,
      minHeight: 148,
      borderRadius: "var(--radius-card)",
      background: "var(--gradient-brand)",
      boxShadow: "var(--shadow-brand)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      position: "absolute",
      right: -70,
      top: -40,
      width: 260,
      height: 260,
      borderRadius: "50%",
      border: "18px solid rgba(255,255,255,.14)",
      pointerEvents: "none"
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      position: "absolute",
      right: 10,
      bottom: -110,
      width: 220,
      height: 220,
      borderRadius: "50%",
      border: "14px solid rgba(255,255,255,.11)",
      pointerEvents: "none"
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "flex-start",
      justifyContent: "space-between",
      gap: 12,
      position: "relative"
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "grid",
      gap: 4
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-medium) var(--text-sm)/1 var(--font-ui)",
      color: "rgba(255,255,255,.86)"
    }
  }, label), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "flex",
      alignItems: "baseline",
      gap: 6
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-bold) var(--text-4xl)/1 var(--font-ui)",
      color: "var(--white)",
      letterSpacing: "var(--tracking-tight)",
      fontVariantNumeric: "tabular-nums"
    }
  }, amount), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-medium) var(--text-sm)/1 var(--font-ui)",
      color: "rgba(255,255,255,.74)"
    }
  }, currency))), /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "plus",
    label: "Add account",
    variant: "plain",
    onClick: onAdd,
    style: {
      background: "var(--white)",
      borderColor: "transparent",
      boxShadow: "var(--shadow-sm)"
    }
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      gap: 8,
      position: "relative"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Button, {
    variant: "secondary",
    iconRight: "arrow-down",
    onClick: onPrimary,
    style: {
      borderColor: "transparent",
      flex: 1
    }
  }, primaryAction), /*#__PURE__*/React.createElement(__ds_scope.Button, {
    variant: "primary",
    iconRight: "arrow-up-right",
    onClick: onSecondary,
    style: {
      flex: 1
    }
  }, secondaryAction)));
}
Object.assign(__ds_scope, { BalanceCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/BalanceCard.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/Card.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Card({
  title,
  subtitle,
  action,
  menu = false,
  onMenu,
  children,
  padding,
  tone = "default",
  radius,
  style,
  bodyStyle,
  ...rest
}) {
  const tones = {
    default: {
      background: "var(--surface-card)",
      border: "1px solid var(--border-subtle)",
      boxShadow: "var(--shadow-card)"
    },
    sunken: {
      background: "var(--surface-sunken)",
      border: "1px solid var(--border-subtle)",
      boxShadow: "none"
    },
    inverse: {
      background: "var(--gradient-ink)",
      border: "1px solid var(--ink-800)",
      boxShadow: "var(--shadow-md)",
      color: "var(--white)"
    },
    brand: {
      background: "var(--gradient-brand)",
      border: "1px solid var(--blue-700)",
      boxShadow: "var(--shadow-brand)",
      color: "var(--white)"
    },
    quiet: {
      background: "var(--surface-card)",
      border: "1px solid transparent",
      boxShadow: "none"
    }
  };
  const inverse = tone === "inverse";
  return /*#__PURE__*/React.createElement("section", _extends({
    style: {
      display: "flex",
      flexDirection: "column",
      minWidth: 0,
      padding: padding != null ? padding : "var(--card-padding)",
      borderRadius: radius || "var(--radius-card)",
      ...tones[tone],
      ...style
    }
  }, rest), title || action || menu ? /*#__PURE__*/React.createElement("header", {
    style: {
      display: "flex",
      alignItems: "flex-start",
      justifyContent: "space-between",
      gap: 12,
      marginBottom: 12
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "grid",
      gap: 2,
      minWidth: 0
    }
  }, title ? /*#__PURE__*/React.createElement("h3", {
    style: {
      font: "var(--type-card-title)",
      color: inverse ? "var(--white)" : "var(--text-heading)",
      overflow: "hidden",
      textOverflow: "ellipsis",
      whiteSpace: "nowrap"
    }
  }, title) : null, subtitle ? /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      font: "var(--type-caption)",
      color: inverse ? "var(--ink-300)" : "var(--text-muted)"
    }
  }, subtitle) : null), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      gap: 6,
      flex: "none"
    }
  }, action, menu ? /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "more-horizontal",
    label: "More",
    size: "sm",
    variant: "bare",
    onClick: onMenu
  }) : null)) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      flexDirection: "column",
      minWidth: 0,
      flex: 1,
      ...bodyStyle
    }
  }, children));
}
function CardFooterLink({
  children,
  icon = "arrow-right",
  onClick
}) {
  return /*#__PURE__*/React.createElement("button", {
    type: "button",
    onClick: onClick,
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 6,
      marginTop: 12,
      padding: 0,
      border: "none",
      background: "transparent",
      cursor: "pointer",
      font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, children, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 13
  }));
}
Object.assign(__ds_scope, { Card, CardFooterLink });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/Card.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/MetricCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function MetricCard({
  eyebrow = "Metric",
  value,
  description,
  filled = 0,
  total = 12,
  tone = "neutral",
  style,
  ...rest
}) {
  const brand = tone === "brand";
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 14,
      justifyItems: "center",
      textAlign: "center",
      padding: "26px 24px",
      borderRadius: "var(--radius-3xl)",
      background: brand ? "var(--surface-brand)" : "linear-gradient(160deg,#F2F2F2 0%,#E4E4E4 100%)",
      boxShadow: brand ? "var(--shadow-brand)" : "var(--shadow-xs)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-medium) var(--text-sm)/1 var(--font-ui)",
      color: brand ? "rgba(255,255,255,.82)" : "var(--gray-600)"
    }
  }, eyebrow), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-bold) var(--text-5xl)/1 var(--font-display)",
      color: brand ? "var(--white)" : "var(--ink-900)",
      letterSpacing: "var(--tracking-tight)"
    }
  }, value), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      maxWidth: 260,
      font: "var(--type-body-sm)",
      color: brand ? "rgba(255,255,255,.82)" : "var(--text-muted)"
    }
  }, description), /*#__PURE__*/React.createElement("span", {
    style: {
      display: "flex",
      gap: 5
    }
  }, Array.from({
    length: total
  }).map((_, i) => /*#__PURE__*/React.createElement("span", {
    key: i,
    style: {
      width: 14,
      height: 14,
      borderRadius: "var(--radius-pill)",
      border: `2.5px solid ${i < filled ? brand ? "var(--white)" : "var(--blue-600)" : brand ? "rgba(255,255,255,.34)" : "var(--white)"}`,
      background: "transparent"
    }
  }))));
}
Object.assign(__ds_scope, { MetricCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/MetricCard.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/StatTile.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function StatTile({
  label,
  value,
  icon,
  delta,
  deltaTone = "success",
  caption,
  chip,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 10,
      padding: 14,
      minWidth: 0,
      background: "var(--surface-card)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-md)",
      boxShadow: "var(--shadow-xs)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "center",
      justifyContent: "space-between",
      gap: 8
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 7,
      minWidth: 0
    }
  }, icon ? /*#__PURE__*/React.createElement("span", {
    style: {
      display: "grid",
      placeItems: "center",
      width: 24,
      height: 24,
      flex: "none",
      borderRadius: "var(--radius-sm)",
      background: "var(--gray-50)",
      border: "1px solid var(--border-subtle)"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 13,
    color: "var(--gray-600)"
  })) : null, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-label)",
      color: "var(--text-muted)",
      overflow: "hidden",
      textOverflow: "ellipsis",
      whiteSpace: "nowrap"
    }
  }, label)), chip ? /*#__PURE__*/React.createElement(__ds_scope.Badge, {
    tone: "success",
    size: "sm"
  }, chip) : null), /*#__PURE__*/React.createElement("div", {
    style: {
      display: "flex",
      alignItems: "baseline",
      gap: 8,
      flexWrap: "wrap"
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)",
      color: "var(--ink-900)",
      letterSpacing: "var(--tracking-snug)",
      fontVariantNumeric: "tabular-nums"
    }
  }, value), delta ? /*#__PURE__*/React.createElement("span", {
    style: {
      display: "inline-flex",
      alignItems: "center",
      gap: 2,
      font: "var(--weight-semibold) var(--text-2xs)/1 var(--font-ui)",
      color: deltaTone === "danger" ? "var(--red-600)" : "var(--status-success-fg)"
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: deltaTone === "danger" ? "trending-down" : "trending-up",
    size: 12
  }), delta) : null), caption ? /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--type-caption)",
      color: "var(--text-subtle)"
    }
  }, caption) : null);
}
Object.assign(__ds_scope, { StatTile });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/StatTile.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/UpgradeCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function UpgradeCard({
  title = "Upgrade plan",
  body = "Upgrade Fynix today to unlock smarter insights and financial control.",
  cta = "Upgrade your Plan",
  onCta,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      display: "grid",
      gap: 8,
      justifyItems: "center",
      textAlign: "center",
      padding: "16px 14px 14px",
      background: "var(--white)",
      border: "1px solid var(--border-subtle)",
      borderRadius: "var(--radius-lg)",
      boxShadow: "var(--shadow-card)",
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("img", {
    src: "../../assets/logo-mark.png",
    alt: "",
    style: {
      width: 22,
      opacity: 0.9
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)",
      color: "var(--ink-900)"
    }
  }, title), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      font: "var(--type-caption)",
      color: "var(--text-muted)"
    }
  }, "\u201C", body, "\u201D"), /*#__PURE__*/React.createElement(__ds_scope.Button, {
    size: "sm",
    iconRight: "arrow-up-right",
    fullWidth: true,
    onClick: onCta,
    style: {
      marginTop: 4
    }
  }, cta));
}
Object.assign(__ds_scope, { UpgradeCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/UpgradeCard.jsx", error: String((e && e.message) || e) }); }

// ui_kits/dashboard/AiAssistant.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
(() => {
  const {
    Card,
    AssistantOrb,
    AssistantComposer,
    SuggestionCard,
    Button
  } = window.UpscaleHubDesignSystem_b4eac1;
  function AiAssistant() {
    const [sent, setSent] = React.useState([]);
    const [draft, setDraft] = React.useState("");
    const send = () => {
      const text = draft.trim();
      if (!text) return;
      setSent(s => [...s, {
        q: text,
        a: "Here is what I found across your accounts — spending is 6% below your monthly average, and your projected balance at month end is $22,410."
      }]);
      setDraft("");
    };
    return /*#__PURE__*/React.createElement(Card, {
      style: {
        minHeight: 560,
        padding: "var(--card-padding-lg)"
      }
    }, sent.length === 0 ? /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        justifyItems: "center",
        gap: 10,
        textAlign: "center",
        padding: "36px 0 22px"
      }
    }, /*#__PURE__*/React.createElement(AssistantOrb, {
      size: 84
    }), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-body-sm)",
        color: "var(--text-muted)"
      }
    }, "Good to see you!"), /*#__PURE__*/React.createElement("h2", {
      style: {
        font: "var(--weight-semibold) var(--text-xl)/1.2 var(--font-ui)",
        color: "var(--ink-900)"
      }
    }, "How Can I Assist You With Your Finances?"), /*#__PURE__*/React.createElement("p", {
      style: {
        margin: 0,
        maxWidth: 420,
        font: "var(--type-body-sm)",
        color: "var(--text-muted)"
      }
    }, "Quickly track cash flow, get AI-powered insights, and manage your money\u2014all in one place")) : /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 14,
        padding: "10px 0 22px",
        maxWidth: 680,
        margin: "0 auto",
        width: "100%"
      }
    }, sent.map((m, i) => /*#__PURE__*/React.createElement(React.Fragment, {
      key: i
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        justifySelf: "end",
        maxWidth: "80%",
        padding: "10px 14px",
        background: "var(--surface-brand)",
        borderRadius: "var(--radius-lg) var(--radius-lg) var(--radius-xs) var(--radius-lg)",
        font: "var(--type-body-sm)",
        color: "var(--white)"
      }
    }, m.q), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 10,
        maxWidth: "88%"
      }
    }, /*#__PURE__*/React.createElement(AssistantOrb, {
      size: 26,
      style: {
        marginTop: 2
      }
    }), /*#__PURE__*/React.createElement("div", {
      style: {
        padding: "10px 14px",
        background: "var(--surface-sunken)",
        border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-lg) var(--radius-lg) var(--radius-lg) var(--radius-xs)",
        font: "var(--type-body-sm)",
        color: "var(--ink-900)"
      }
    }, m.a))))), /*#__PURE__*/React.createElement("div", {
      style: {
        maxWidth: 680,
        width: "100%",
        margin: "0 auto"
      }
    }, /*#__PURE__*/React.createElement(AssistantComposer, {
      value: draft,
      onChange: e => setDraft(e.target.value),
      onSend: send
    })), sent.length === 0 ? /*#__PURE__*/React.createElement("div", {
      style: {
        maxWidth: 680,
        width: "100%",
        margin: "22px auto 0"
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        display: "block",
        marginBottom: 10,
        font: "var(--type-label)",
        color: "var(--text-muted)"
      }
    }, "Get started with an example below"), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "1fr 1fr",
        gap: 10
      }
    }, window.FX.aiPrompts.map(p => /*#__PURE__*/React.createElement(SuggestionCard, _extends({
      key: p.title
    }, p, {
      onClick: () => {
        setDraft(p.title);
      }
    }))))) : null);
  }
  Object.assign(window, {
    AiAssistant
  });
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/dashboard/AiAssistant.jsx", error: String((e && e.message) || e) }); }

// ui_kits/dashboard/AppShell.jsx
try { (() => {
(() => {
  const {
    SidebarNav,
    AccountSwitcher,
    TopBar,
    UpgradeCard,
    Button,
    IconButton
  } = window.UpscaleHubDesignSystem_b4eac1;
  const SHELL_TITLES = {
    dash: {
      title: "Home",
      subtitle: "Track finances easily with AI insights and recommendations."
    },
    ai: {
      title: "Fynix AI Assistant",
      subtitle: "Get smart financial insights, forecasts, and tips."
    },
    tx: {
      title: "Transactions",
      subtitle: "View, track, and manage all expenses with ease."
    },
    wallet: {
      title: "Wallet",
      subtitle: "Securely store, track, and manage your money."
    },
    inv: {
      title: "Invoices",
      subtitle: "Issue, send, and reconcile invoices in one place."
    },
    rep: {
      title: "Reports",
      subtitle: "Build and export reports across every account."
    },
    set: {
      title: "Settings",
      subtitle: "Manage your profile, security, and preferences."
    },
    help: {
      title: "Help Center",
      subtitle: "Search guides or reach the Fynix team."
    }
  };
  function AppShell({
    page,
    onNavigate,
    actions,
    children
  }) {
    const [collapsed, setCollapsed] = React.useState(false);
    const meta = SHELL_TITLES[page] || SHELL_TITLES.dash;
    return /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        minHeight: "100vh",
        background: "var(--surface-canvas)"
      }
    }, /*#__PURE__*/React.createElement(SidebarNav, {
      sections: window.FX.nav,
      active: page,
      onNavigate: onNavigate,
      collapsed: collapsed,
      header: !collapsed ? /*#__PURE__*/React.createElement(AccountSwitcher, {
        name: window.FX.user.name,
        role: window.FX.user.role
      }) : null,
      footer: !collapsed ? /*#__PURE__*/React.createElement(UpgradeCard, null) : null,
      style: {
        position: "sticky",
        top: 0,
        height: "100vh"
      }
    }), /*#__PURE__*/React.createElement("div", {
      style: {
        flex: 1,
        minWidth: 0,
        display: "flex",
        flexDirection: "column"
      }
    }, /*#__PURE__*/React.createElement(TopBar, {
      title: meta.title,
      subtitle: meta.subtitle,
      userName: window.FX.user.name,
      actions: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(IconButton, {
        icon: collapsed ? "panel-left-open" : "panel-left-close",
        label: "Toggle sidebar",
        variant: "bare",
        onClick: () => setCollapsed(c => !c)
      }), actions)
    }), /*#__PURE__*/React.createElement("main", {
      style: {
        flex: 1,
        minWidth: 0,
        padding: "var(--page-padding)"
      }
    }, children)));
  }
  function Placeholder({
    title
  }) {
    return /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        placeItems: "center",
        minHeight: 420,
        gap: 10,
        textAlign: "center"
      }
    }, /*#__PURE__*/React.createElement("img", {
      src: "../../assets/logo-mark.png",
      alt: "",
      style: {
        width: 34,
        opacity: 0.35
      }
    }), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-card-title)",
        color: "var(--ink-900)"
      }
    }, title), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-body-sm)",
        color: "var(--text-muted)",
        maxWidth: 380
      }
    }, "Not present in the supplied case study \u2014 intentionally left blank rather than invented."));
  }
  Object.assign(window, {
    AppShell,
    Placeholder
  });
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/dashboard/AppShell.jsx", error: String((e && e.message) || e) }); }

// ui_kits/dashboard/DashboardHome.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
(() => {
  const {
    Card,
    CardFooterLink,
    StatTile,
    BalanceCard,
    Button,
    IconButton,
    Badge,
    Select,
    Tabs,
    ProgressBar,
    BarChart,
    ChartLegend,
    DonutChart,
    CategoryLegend,
    DataTable,
    TransactionCell,
    Amount,
    ExchangePanel,
    AssistantComposer,
    AssistantOrb,
    Icon
  } = window.UpscaleHubDesignSystem_b4eac1;
  function FinanceScore() {
    return /*#__PURE__*/React.createElement(Card, {
      title: "Finance Score",
      menu: true
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 10
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Finance Quality"), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "baseline",
        justifyContent: "space-between",
        gap: 10
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)",
        color: "var(--ink-900)"
      }
    }, "Excellent"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)",
        color: "var(--ink-900)"
      }
    }, "92%")), /*#__PURE__*/React.createElement(ProgressBar, {
      height: 16,
      segments: [{
        value: 70,
        color: "var(--chart-1)"
      }, {
        value: 22,
        color: "var(--chart-2)"
      }]
    }), /*#__PURE__*/React.createElement(CardFooterLink, null, "Improve my score")));
  }
  function DashboardHome({
    onNavigate
  }) {
    const [range, setRange] = React.useState("This Year");
    const [txRange, setTxRange] = React.useState("This Month");
    const [statTab, setStatTab] = React.useState("expense");
    const cols = [{
      key: "name",
      label: "Transaction Name",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement(TransactionCell, {
        icon: r.icon,
        title: r.name,
        meta: r.cat,
        tone: r.tone
      })
    }, {
      key: "acct",
      label: "Account",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          display: "inline-flex",
          alignItems: "center",
          gap: 8
        }
      }, /*#__PURE__*/React.createElement("span", {
        style: {
          display: "grid",
          placeItems: "center",
          width: 26,
          height: 17,
          borderRadius: 3,
          background: r.brand === "VISA" ? "var(--blue-600)" : "var(--gray-200)",
          font: "var(--weight-bold) 6px/1 var(--font-ui)",
          color: r.brand === "VISA" ? "var(--white)" : "var(--ink-900)"
        }
      }, r.brand), /*#__PURE__*/React.createElement("span", {
        style: {
          color: "var(--gray-600)"
        }
      }, r.acct))
    }, {
      key: "date",
      label: "Date & Time",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          display: "grid",
          gap: 1
        }
      }, /*#__PURE__*/React.createElement("span", {
        style: {
          fontVariantNumeric: "tabular-nums"
        }
      }, r.date), /*#__PURE__*/React.createElement("span", {
        style: {
          font: "var(--type-caption)",
          color: "var(--text-subtle)"
        }
      }, r.time))
    }, {
      key: "amt",
      label: "Amount",
      align: "right",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement(Amount, {
        value: r.amt
      })
    }, {
      key: "status",
      label: "Status",
      align: "right",
      render: r => /*#__PURE__*/React.createElement(Badge, {
        tone: r.status === "Completed" ? "success" : "warning"
      }, r.status)
    }];
    return /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: "var(--card-gap)"
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "252px minmax(0,1fr) 280px",
        gap: "var(--card-gap)",
        alignItems: "stretch"
      }
    }, /*#__PURE__*/React.createElement(BalanceCard, {
      amount: "$20,670",
      currency: "USD",
      onPrimary: () => onNavigate("wallet"),
      onSecondary: () => onNavigate("wallet")
    }), /*#__PURE__*/React.createElement(Card, {
      title: "AI Enhancements",
      action: /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "secondary",
        icon: "plus",
        style: {
          flex: "none"
        }
      }, "Add")
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "repeat(3,minmax(0,1fr))",
        gap: 10
      }
    }, window.FX.enhancements.map(e => /*#__PURE__*/React.createElement(StatTile, _extends({
      key: e.label
    }, e))))), /*#__PURE__*/React.createElement(FinanceScore, null)), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "minmax(0,1.62fr) minmax(0,1fr)",
        gap: "var(--card-gap)"
      }
    }, /*#__PURE__*/React.createElement(Card, {
      title: "Cashflow",
      action: /*#__PURE__*/React.createElement(Select, {
        options: ["This Year", "This Month", "All time"],
        value: range,
        onChange: setRange
      })
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "space-between",
        gap: 16,
        marginBottom: 10
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 2
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Total Balance"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-2xl)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums",
        color: "var(--ink-900)"
      }
    }, "$562,000")), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        gap: 14
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 3,
        padding: "8px 10px",
        background: "var(--surface-sunken)",
        border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-sm)"
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "June 2029"), /*#__PURE__*/React.createElement("span", {
      style: {
        display: "flex",
        gap: 10,
        font: "var(--type-caption)",
        color: "var(--gray-600)"
      }
    }, /*#__PURE__*/React.createElement("span", null, "Income"), /*#__PURE__*/React.createElement("b", {
      style: {
        fontVariantNumeric: "tabular-nums"
      }
    }, "$6,000")), /*#__PURE__*/React.createElement("span", {
      style: {
        display: "flex",
        gap: 10,
        font: "var(--type-caption)",
        color: "var(--gray-600)"
      }
    }, /*#__PURE__*/React.createElement("span", null, "Expense"), /*#__PURE__*/React.createElement("b", {
      style: {
        fontVariantNumeric: "tabular-nums"
      }
    }, "$4,000"))), /*#__PURE__*/React.createElement(ChartLegend, {
      items: [{
        label: "Income",
        color: "var(--chart-1)"
      }, {
        label: "Expense",
        color: "var(--chart-2)"
      }]
    }))), /*#__PURE__*/React.createElement(BarChart, {
      data: window.FX.cashflow,
      height: 186,
      formatTick: v => v === 0 ? "0" : (v / 1000).toFixed(0) + "K"
    })), /*#__PURE__*/React.createElement(Card, {
      title: "AI Assistant",
      menu: true
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 10,
        justifyItems: "center",
        textAlign: "center",
        padding: "6px 0 12px"
      }
    }, /*#__PURE__*/React.createElement(AssistantOrb, {
      size: 64
    }), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-card-title)",
        color: "var(--ink-900)"
      }
    }, "What Can I help with?"), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        flexWrap: "wrap",
        gap: 6,
        justifyContent: "center"
      }
    }, ["Show me my cash flow", "Help me set a savings goal", "Forecast my balance", "Plan my monthly budget", "Detect unusual transactions", "Others"].map(c => /*#__PURE__*/React.createElement("span", {
      key: c,
      style: {
        display: "inline-flex",
        alignItems: "center",
        gap: 5,
        height: 24,
        padding: "0 10px",
        background: "var(--gray-50)",
        border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-pill)",
        font: "var(--type-caption)",
        color: "var(--gray-600)"
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "sparkles",
      size: 11,
      color: "var(--green-700)"
    }), c)))), /*#__PURE__*/React.createElement(AssistantComposer, {
      style: {
        marginTop: "auto"
      },
      onSend: () => onNavigate("ai")
    }))), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "minmax(0,1.9fr) minmax(0,1fr) minmax(0,1fr)",
        gap: "var(--card-gap)"
      }
    }, /*#__PURE__*/React.createElement(Card, {
      title: "Recent Transactions",
      padding: 0,
      style: {
        padding: "16px 0 0"
      },
      action: /*#__PURE__*/React.createElement("div", {
        style: {
          display: "flex",
          alignItems: "center",
          gap: 6,
          paddingRight: 16
        }
      }, /*#__PURE__*/React.createElement(Select, {
        options: ["This Month", "This Year"],
        value: txRange,
        onChange: setTxRange
      }), /*#__PURE__*/React.createElement(IconButton, {
        icon: "sliders-horizontal",
        label: "Filters",
        size: "sm",
        variant: "bare"
      })),
      bodyStyle: {
        padding: 0
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        padding: "0 16px 12px",
        marginTop: -8
      }
    }), /*#__PURE__*/React.createElement(DataTable, {
      columns: cols,
      rows: window.FX.transactions.slice(0, 5),
      dense: true
    })), /*#__PURE__*/React.createElement(Card, {
      title: "Statistic",
      action: /*#__PURE__*/React.createElement(Select, {
        options: ["This Month", "This Year"],
        value: "This Month"
      })
    }, /*#__PURE__*/React.createElement(Tabs, {
      style: {
        marginBottom: 12
      },
      items: [{
        id: "income",
        label: "Income",
        meta: "($4,800)"
      }, {
        id: "expense",
        label: "Expense",
        meta: "($3,500)"
      }],
      value: statTab,
      onChange: setStatTab
    }), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        justifyItems: "center",
        gap: 14
      }
    }, /*#__PURE__*/React.createElement(DonutChart, {
      size: 140,
      thickness: 20,
      label: "Total Expense",
      value: "$3,500",
      segments: window.FX.expenseSegments
    }), /*#__PURE__*/React.createElement(CategoryLegend, {
      style: {
        width: "100%"
      },
      items: window.FX.expenseSegments.map((s, i) => ({
        pct: s.pct,
        label: s.label,
        value: "$" + s.value.toLocaleString(),
        color: s.color,
        pctColor: i === 0 ? "var(--white)" : "var(--ink-900)"
      }))
    }))), /*#__PURE__*/React.createElement(Card, {
      title: "Exchange",
      action: /*#__PURE__*/React.createElement(Badge, {
        tone: "outline"
      }, "Currencies")
    }, /*#__PURE__*/React.createElement(ExchangePanel, null))));
  }
  Object.assign(window, {
    DashboardHome
  });
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/dashboard/DashboardHome.jsx", error: String((e && e.message) || e) }); }

// ui_kits/dashboard/Transactions.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
(() => {
  const {
    Card,
    StatTile,
    Badge,
    Button,
    IconButton,
    Select,
    Input,
    Checkbox,
    DataTable,
    TransactionCell,
    Amount,
    AreaChart,
    CreditCardItem,
    ActionTile,
    Icon
  } = window.UpscaleHubDesignSystem_b4eac1;
  function Transactions() {
    const [selected, setSelected] = React.useState([]);
    const [query, setQuery] = React.useState("");
    const [cat, setCat] = React.useState("All Category");
    const rows = window.FX.transactions.filter(r => (cat === "All Category" || r.cat === cat) && (query === "" || r.name.toLowerCase().includes(query.toLowerCase())));
    const cols = [{
      key: "name",
      label: "Transaction Name",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement(TransactionCell, {
        icon: r.icon,
        title: r.name,
        meta: r.cat,
        tone: r.tone
      })
    }, {
      key: "acct",
      label: "Account",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          display: "inline-flex",
          alignItems: "center",
          gap: 8
        }
      }, /*#__PURE__*/React.createElement("span", {
        style: {
          display: "grid",
          placeItems: "center",
          width: 26,
          height: 17,
          borderRadius: 3,
          background: r.brand === "VISA" ? "var(--blue-600)" : "var(--gray-200)",
          font: "var(--weight-bold) 6px/1 var(--font-ui)",
          color: r.brand === "VISA" ? "var(--white)" : "var(--ink-900)"
        }
      }, r.brand), /*#__PURE__*/React.createElement("span", {
        style: {
          color: "var(--gray-600)"
        }
      }, r.acct))
    }, {
      key: "id",
      label: "Transaction ID",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          fontFamily: "var(--font-accent)",
          color: "var(--gray-600)"
        }
      }, r.id)
    }, {
      key: "date",
      label: "Date & Time",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          display: "grid",
          gap: 1
        }
      }, /*#__PURE__*/React.createElement("span", {
        style: {
          fontVariantNumeric: "tabular-nums"
        }
      }, r.date), /*#__PURE__*/React.createElement("span", {
        style: {
          font: "var(--type-caption)",
          color: "var(--text-subtle)"
        }
      }, r.time))
    }, {
      key: "amt",
      label: "Amount",
      align: "right",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement(Amount, {
        value: r.amt
      })
    }, {
      key: "note",
      label: "Note",
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          color: "var(--gray-600)"
        }
      }, r.note)
    }, {
      key: "status",
      label: "Status",
      align: "right",
      render: r => /*#__PURE__*/React.createElement(Badge, {
        tone: r.status === "Completed" ? "success" : "warning"
      }, r.status)
    }];
    return /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: "var(--card-gap)"
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "minmax(0,1fr) minmax(0,1fr) minmax(0,1.5fr)",
        gap: "var(--card-gap)",
        alignItems: "stretch"
      }
    }, /*#__PURE__*/React.createElement(Card, {
      title: "Total Balance",
      action: /*#__PURE__*/React.createElement(Badge, {
        tone: "outline",
        icon: "eye"
      }, "Hidden")
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "baseline",
        gap: 8
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-2xl)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums"
      }
    }, "$1,750.82"), /*#__PURE__*/React.createElement(Badge, {
      tone: "success",
      size: "sm"
    }, "Last 7 days")), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 8,
        marginTop: 12
      }
    }, /*#__PURE__*/React.createElement(Button, {
      size: "sm",
      variant: "secondary",
      icon: "arrow-left-right",
      style: {
        flex: 1
      }
    }, "Transfer Funds"), /*#__PURE__*/React.createElement(Button, {
      size: "sm",
      variant: "brand",
      icon: "plus",
      style: {
        flex: 1
      }
    }, "Fund Request")), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "repeat(3,1fr)",
        gap: 8,
        marginTop: 14
      }
    }, [["Total Expense", "$43,000", "danger"], ["Total Savings", "$56,000", "success"], ["Total Income", "$78,000", "success"]].map(([l, v, t]) => /*#__PURE__*/React.createElement("div", {
      key: l,
      style: {
        display: "grid",
        gap: 4,
        padding: 10,
        background: "var(--surface-sunken)",
        border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-sm)"
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: t === "danger" ? "trending-down" : "trending-up",
      size: 13,
      color: t === "danger" ? "var(--red-600)" : "var(--status-success-fg)"
    }), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-md)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums"
      }
    }, v), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, l))))), /*#__PURE__*/React.createElement(Card, {
      title: "Your Cards",
      action: /*#__PURE__*/React.createElement(IconButton, {
        icon: "plus",
        label: "Add card",
        size: "sm",
        variant: "bare"
      })
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 8
      }
    }, window.FX.cards.map((c, i) => /*#__PURE__*/React.createElement(CreditCardItem, _extends({
      key: i
    }, c, {
      selected: i === 0
    }))), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 8
      }
    }, /*#__PURE__*/React.createElement(ActionTile, {
      icon: "plus",
      label: "Top Up",
      style: {
        flex: 1
      }
    }), /*#__PURE__*/React.createElement(ActionTile, {
      icon: "circle-dollar-sign",
      label: "Transfer",
      style: {
        flex: 1
      }
    }), /*#__PURE__*/React.createElement(ActionTile, {
      icon: "credit-card",
      label: "Payment",
      style: {
        flex: 1
      }
    })))), /*#__PURE__*/React.createElement(Card, {
      title: "Cash Flow",
      menu: true
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 22,
        marginBottom: 12
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        display: "grid",
        gap: 2
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        display: "inline-flex",
        alignItems: "center",
        gap: 6,
        font: "var(--type-caption)",
        color: "var(--text-muted)"
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "circle-arrow-down",
      size: 13,
      color: "var(--green-700)"
    }), "Income"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-md)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums"
      }
    }, "$5,772.13")), /*#__PURE__*/React.createElement("span", {
      style: {
        display: "grid",
        gap: 2
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        display: "inline-flex",
        alignItems: "center",
        gap: 6,
        font: "var(--type-caption)",
        color: "var(--text-muted)"
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "circle-arrow-up",
      size: 13,
      color: "var(--ink-900)"
    }), "Expenses"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-md)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums"
      }
    }, "$881.90")), /*#__PURE__*/React.createElement("span", {
      style: {
        marginLeft: "auto",
        display: "grid",
        gap: 2,
        padding: "8px 10px",
        background: "var(--surface-sunken)",
        border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-sm)"
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Monthly"), /*#__PURE__*/React.createElement("span", {
      style: {
        display: "flex",
        alignItems: "baseline",
        gap: 6
      }
    }, /*#__PURE__*/React.createElement("b", {
      style: {
        font: "var(--weight-bold) var(--text-sm)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums"
      }
    }, "$8,903"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--status-success-fg)"
      }
    }, "+1.9%")), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Compared to $8,441 last month"))), /*#__PURE__*/React.createElement(AreaChart, {
      height: 150,
      series: [{
        points: window.FX.cashflowLine,
        color: "var(--green-500)"
      }, {
        points: window.FX.cashflowLine2,
        color: "var(--ink-900)",
        fill: false,
        width: 1.6
      }],
      yTicks: ["$50", "$40", "$30", "$20", "$10", "$0"],
      xLabels: window.MONTHS,
      marker: 6
    }))), /*#__PURE__*/React.createElement(Card, {
      title: "Recent Transactions",
      padding: 0,
      style: {
        padding: "16px 0 0"
      },
      bodyStyle: {
        padding: 0
      },
      action: /*#__PURE__*/React.createElement("div", {
        style: {
          display: "flex",
          alignItems: "center",
          gap: 8,
          paddingRight: 16
        }
      }, /*#__PURE__*/React.createElement(Select, {
        options: ["1–30 September 2028", "1–31 August 2028"],
        value: "1\u201330 September 2028"
      }), /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        icon: "download"
      }, "Download"))
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        gap: 10,
        padding: "0 16px 14px"
      }
    }, /*#__PURE__*/React.createElement(Input, {
      style: {
        width: 240
      },
      icon: "search",
      placeholder: "Search transaction",
      value: query,
      onChange: e => setQuery(e.target.value)
    }), /*#__PURE__*/React.createElement(Select, {
      variant: "field",
      width: 150,
      options: ["All Category", "Investments", "Food & Dining", "Utilities", "Services", "Shopping", "Income"],
      value: cat,
      onChange: setCat
    }), /*#__PURE__*/React.createElement(Select, {
      variant: "field",
      width: 140,
      options: ["All Account", "Platinum Plus Visa", "Freedom Mastercard"],
      value: "All Account"
    }), selected.length ? /*#__PURE__*/React.createElement("span", {
      style: {
        marginLeft: "auto",
        display: "inline-flex",
        alignItems: "center",
        gap: 10
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-muted)"
      }
    }, selected.length, " selected"), /*#__PURE__*/React.createElement(Button, {
      size: "sm",
      variant: "ghost",
      icon: "tag"
    }, "Categorise"), /*#__PURE__*/React.createElement(Button, {
      size: "sm",
      variant: "danger",
      icon: "trash-2"
    }, "Delete")) : null), /*#__PURE__*/React.createElement(DataTable, {
      columns: cols,
      rows: rows,
      selectable: true,
      selected: selected,
      onSelect: setSelected
    })));
  }
  Object.assign(window, {
    Transactions
  });
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/dashboard/Transactions.jsx", error: String((e && e.message) || e) }); }

// ui_kits/dashboard/Wallet.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
(() => {
  const {
    Card,
    Badge,
    Button,
    IconButton,
    Select,
    Tabs,
    ProgressBar,
    DonutChart,
    CategoryLegend,
    AreaChart,
    DataTable,
    TransactionCell,
    Amount,
    CreditCardItem,
    ActionTile,
    Icon,
    Input
  } = window.UpscaleHubDesignSystem_b4eac1;
  function Wallet() {
    const [card, setCard] = React.useState(0);
    const [walletTab, setWalletTab] = React.useState("wallet");
    const active = window.FX.cards[card];
    const cols = [{
      key: "name",
      label: "Transaction Name",
      sortable: true,
      render: r => /*#__PURE__*/React.createElement(TransactionCell, {
        icon: r.icon,
        title: r.name,
        meta: r.cat,
        tone: r.tone
      })
    }, {
      key: "id",
      label: "Transaction ID",
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          fontFamily: "var(--font-accent)",
          color: "var(--gray-600)"
        }
      }, r.id)
    }, {
      key: "date",
      label: "Date & Time",
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          display: "grid",
          gap: 1
        }
      }, /*#__PURE__*/React.createElement("span", {
        style: {
          fontVariantNumeric: "tabular-nums"
        }
      }, r.date), /*#__PURE__*/React.createElement("span", {
        style: {
          font: "var(--type-caption)",
          color: "var(--text-subtle)"
        }
      }, r.time))
    }, {
      key: "amt",
      label: "Amount",
      align: "right",
      render: r => /*#__PURE__*/React.createElement(Amount, {
        value: r.amt
      })
    }, {
      key: "status",
      label: "Status",
      align: "right",
      render: r => /*#__PURE__*/React.createElement(Badge, {
        tone: r.status === "Completed" ? "success" : "warning"
      }, r.status)
    }];
    return /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: "var(--card-gap)"
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "flex-end",
        justifyContent: "space-between",
        gap: 16,
        padding: "2px 2px 0"
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 3
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-semibold) var(--text-md)/1.2 var(--font-ui)",
        color: "var(--ink-900)"
      }
    }, "Overview / ", /*#__PURE__*/React.createElement("span", {
      style: {
        fontStyle: "italic",
        fontWeight: 500
      }
    }, "Balance Details")), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-bold) var(--text-2xl)/1.1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums",
        color: "var(--ink-900)"
      }
    }, "$542.25.00 USD"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Your total balance estimate in USD at 2024-09-16 12:20")), /*#__PURE__*/React.createElement(Button, {
      size: "sm",
      variant: "soft",
      icon: "settings-2"
    }, "Manage Balance")), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "minmax(0,1fr) minmax(0,1fr) minmax(0,1.6fr)",
        gap: "var(--card-gap)",
        alignItems: "stretch"
      }
    }, /*#__PURE__*/React.createElement(Card, {
      title: "Your Cards",
      action: /*#__PURE__*/React.createElement(IconButton, {
        icon: "plus",
        label: "Add card",
        size: "sm",
        variant: "bare"
      })
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 8
      }
    }, window.FX.cards.map((c, i) => /*#__PURE__*/React.createElement(CreditCardItem, _extends({
      key: i
    }, c, {
      selected: i === card,
      onSelect: () => setCard(i)
    }))))), /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 8,
        marginBottom: 14
      }
    }, /*#__PURE__*/React.createElement(ActionTile, {
      icon: "plus",
      label: "Top Up",
      style: {
        flex: 1
      }
    }), /*#__PURE__*/React.createElement(ActionTile, {
      icon: "circle-dollar-sign",
      label: "Transfer",
      style: {
        flex: 1
      }
    }), /*#__PURE__*/React.createElement(ActionTile, {
      icon: "credit-card",
      label: "Payment",
      style: {
        flex: 1
      }
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 4,
        marginBottom: 12
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Card Number"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-medium) var(--text-base)/1 var(--font-accent)",
        color: "var(--ink-900)"
      }
    }, "5582 5574 8376 5487")), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 18,
        marginBottom: 14
      }
    }, [["Expiry Date", "08/25"], ["CVC", "40"]].map(([l, v]) => /*#__PURE__*/React.createElement("span", {
      key: l,
      style: {
        display: "grid",
        gap: 3
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, l), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-medium) var(--text-sm)/1 var(--font-accent)"
      }
    }, v))), /*#__PURE__*/React.createElement("span", {
      style: {
        display: "grid",
        gap: 3
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Status"), /*#__PURE__*/React.createElement(Badge, {
      tone: "inverse",
      size: "sm"
    }, "Active"))), /*#__PURE__*/React.createElement("div", {
      style: {
        paddingTop: 12,
        borderTop: "1px solid var(--border-subtle)"
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        marginBottom: 8
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)"
      }
    }, "Spending Limits"), /*#__PURE__*/React.createElement(IconButton, {
      icon: "more-vertical",
      label: "Limit options",
      size: "sm",
      variant: "bare"
    })), /*#__PURE__*/React.createElement(ProgressBar, {
      height: 10,
      value: 45,
      label: "$4,500.00 spent of $10,000.00",
      valueLabel: "45%"
    }))), /*#__PURE__*/React.createElement(Card, {
      title: "My Wallets",
      action: /*#__PURE__*/React.createElement(Select, {
        options: ["Monthly", "Weekly", "Daily"],
        value: "Monthly"
      })
    }, /*#__PURE__*/React.createElement(Tabs, {
      style: {
        marginBottom: 14
      },
      items: [{
        id: "wallet",
        label: "Wallet"
      }, {
        id: "card",
        label: "Card Transaction"
      }, {
        id: "inv",
        label: "Investment"
      }],
      value: walletTab,
      onChange: setWalletTab
    }), /*#__PURE__*/React.createElement(AreaChart, {
      height: 176,
      series: [{
        points: window.FX.walletBalance,
        color: "var(--chart-1)"
      }],
      yTicks: ["$100k", "$80k", "$60k", "$40k", "$20k", "0"],
      xLabels: ["1", "2", "3", "4", "5", "6", "7", "8"],
      marker: 16
    }))), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gridTemplateColumns: "minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr)",
        gap: "var(--card-gap)",
        alignItems: "stretch"
      }
    }, /*#__PURE__*/React.createElement(Card, {
      title: "Transactions",
      padding: 0,
      style: {
        padding: "16px 0 0"
      },
      bodyStyle: {
        padding: 0
      },
      action: /*#__PURE__*/React.createElement("div", {
        style: {
          paddingRight: 16
        }
      }, /*#__PURE__*/React.createElement(Select, {
        options: ["This Month", "This Year"],
        value: "This Month"
      }))
    }, /*#__PURE__*/React.createElement(DataTable, {
      columns: cols,
      rows: window.FX.transactions.slice(0, 5),
      dense: true
    })), /*#__PURE__*/React.createElement(Card, {
      title: "All Expenses",
      action: /*#__PURE__*/React.createElement(Select, {
        options: ["This Month", "This Year"],
        value: "This Month"
      })
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        gap: 10,
        marginBottom: 14
      }
    }, window.FX.expenseTotals.map(t => /*#__PURE__*/React.createElement("span", {
      key: t.label,
      style: {
        display: "grid",
        gap: 3
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, t.label), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)",
        fontVariantNumeric: "tabular-nums"
      }
    }, t.value)))), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        gap: 12
      }
    }, /*#__PURE__*/React.createElement(DonutChart, {
      size: 118,
      thickness: 17,
      label: "Platform",
      value: "Rp225.000",
      segments: [{
        value: 45,
        color: "var(--chart-2)"
      }, {
        value: 25,
        color: "var(--chart-1)"
      }, {
        value: 18,
        color: "var(--chart-4)"
      }, {
        value: 12,
        color: "var(--chart-5)"
      }]
    }), /*#__PURE__*/React.createElement(CategoryLegend, {
      style: {
        flex: 1
      },
      items: [{
        label: "Shopping",
        value: "45%",
        color: "var(--chart-2)"
      }, {
        label: "Platform",
        value: "25%",
        color: "var(--chart-1)"
      }, {
        label: "Food & Drinks",
        value: "18%",
        color: "var(--chart-4)"
      }, {
        label: "Other Expenses",
        value: "12%",
        color: "var(--chart-5)"
      }]
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        gap: 10,
        marginTop: 14,
        paddingTop: 12,
        borderTop: "1px solid var(--border-subtle)"
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        display: "grid",
        placeItems: "center",
        width: 30,
        height: 30,
        borderRadius: "var(--radius-pill)",
        background: "var(--gray-50)",
        border: "1px solid var(--border-subtle)"
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "coins",
      size: 14,
      color: "var(--ink-900)"
    })), /*#__PURE__*/React.createElement("span", {
      style: {
        display: "grid",
        gap: 2
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)"
      }
    }, "Rp70.000 \u2013 Rp100.000"), /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--text-subtle)"
      }
    }, "Spending limit near")))), /*#__PURE__*/React.createElement(Card, {
      title: "Convert",
      menu: true
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 10
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        gap: 8
      }
    }, /*#__PURE__*/React.createElement(Input, {
      style: {
        flex: 1
      },
      label: "You send",
      value: "$200.00"
    }), /*#__PURE__*/React.createElement(Select, {
      style: {
        marginTop: 18
      },
      options: ["USD", "EUR", "GBP"],
      value: "USD",
      leading: /*#__PURE__*/React.createElement("span", null, "\uD83C\uDDFA\uD83C\uDDF8")
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "grid",
        gap: 7,
        padding: 12,
        background: "var(--surface-sunken)",
        border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-md)"
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        font: "var(--type-caption)",
        color: "var(--gray-600)"
      }
    }, "You\u2019ve ", /*#__PURE__*/React.createElement("b", {
      style: {
        color: "var(--ink-900)"
      }
    }, "$35,478.00"), " available balance"), [["\u2212 $2.23", "Our fees"], ["= $197.77", "Amount converted"], ["\u00D7 0.778786", "Live rate"]].map(([a, b]) => /*#__PURE__*/React.createElement("span", {
      key: b,
      style: {
        display: "flex",
        justifyContent: "space-between",
        font: "var(--type-caption)",
        color: "var(--gray-600)"
      }
    }, /*#__PURE__*/React.createElement("b", {
      style: {
        color: "var(--ink-900)",
        fontFamily: "var(--font-accent)"
      }
    }, a), b))), /*#__PURE__*/React.createElement("div", {
      style: {
        display: "flex",
        alignItems: "center",
        gap: 8
      }
    }, /*#__PURE__*/React.createElement(Input, {
      style: {
        flex: 1
      },
      label: "They get",
      value: "\xA3154.02"
    }), /*#__PURE__*/React.createElement(Select, {
      style: {
        marginTop: 18
      },
      options: ["GBP", "EUR", "USD"],
      value: "GBP",
      leading: /*#__PURE__*/React.createElement("span", null, "\uD83C\uDDEC\uD83C\uDDE7")
    })), /*#__PURE__*/React.createElement(Button, {
      variant: "brand",
      pill: false,
      fullWidth: true
    }, "Continue")))));
  }
  Object.assign(window, {
    Wallet
  });
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/dashboard/Wallet.jsx", error: String((e && e.message) || e) }); }

// ui_kits/dashboard/data.js
try { (() => {
// Mock data for the Fynix dashboard UI kit. Figures copied from the source case-study screens.
const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const FX = {
  user: {
    name: "Jenny Wilson",
    role: "Personal Account"
  },
  nav: [{
    title: "Main Menu",
    items: [{
      id: "dash",
      label: "Dashboard",
      icon: "house"
    }, {
      id: "ai",
      label: "AI Fynix",
      icon: "wand-sparkles"
    }, {
      id: "tx",
      label: "Transactions",
      icon: "receipt"
    }, {
      id: "wallet",
      label: "My Wallet",
      icon: "wallet"
    }, {
      id: "inv",
      label: "Invoices",
      icon: "clipboard-list"
    }, {
      id: "rep",
      label: "Reports",
      icon: "chart-pie"
    }]
  }, {
    title: "Preference",
    items: [{
      id: "set",
      label: "Settings",
      icon: "settings"
    }, {
      id: "help",
      label: "Help Center",
      icon: "circle-help"
    }]
  }],
  cashflow: MONTHS.map((m, i) => ({
    label: m,
    income: [5200, 4100, 4800, 5600, 5000, 6100, 5400, 6400, 7000, 5800, 4900, 6600][i],
    expense: [3600, 4200, 3900, 4400, 3700, 4100, 3400, 4600, 3900, 4300, 3500, 4800][i]
  })),
  enhancements: [{
    icon: "arrow-down-left",
    label: "Income",
    value: "$14,480.24",
    chip: "Guide"
  }, {
    icon: "arrow-up-right",
    label: "Expense",
    value: "$14,480.24",
    chip: "Guide"
  }, {
    icon: "piggy-bank",
    label: "Savings",
    value: "$14,480.24",
    chip: "Guide"
  }],
  expenseSegments: [{
    label: "Rent & Living",
    value: 2100,
    color: "var(--chart-1)",
    pct: "60%"
  }, {
    label: "Investment",
    value: 525,
    color: "var(--chart-2)",
    pct: "15%"
  }, {
    label: "Education",
    value: 420,
    color: "var(--chart-4)",
    pct: "12%"
  }, {
    label: "Food & Drinks",
    value: 455,
    color: "var(--chart-5)",
    pct: "13%"
  }],
  transactions: [{
    name: "Dividend Payout",
    cat: "Investments",
    icon: "trending-up",
    tone: "income",
    acct: "Platinum Plus Visa",
    brand: "VISA",
    id: "456789008",
    date: "2024-09-25",
    time: "10:00",
    amt: "+$200.00",
    status: "Completed",
    note: "Quarterly stock dividend"
  }, {
    name: "Grocery Shopping",
    cat: "Food & Dining",
    icon: "shopping-basket",
    tone: "expense",
    acct: "Platinum Plus Visa",
    brand: "VISA",
    id: "456789016",
    date: "2024-09-24",
    time: "14:30",
    amt: "-$184.20",
    status: "Completed",
    note: "Weekly household groceries"
  }, {
    name: "Freelance Payment",
    cat: "Income",
    icon: "circle-dollar-sign",
    tone: "income",
    acct: "Freedom Mastercard",
    brand: "MC",
    id: "456789023",
    date: "2024-09-23",
    time: "15:00",
    amt: "+$850.00",
    status: "Completed",
    note: "Payment for design work"
  }, {
    name: "Electricity Bill",
    cat: "Utilities",
    icon: "zap",
    tone: "expense",
    acct: "Freedom Mastercard",
    brand: "MC",
    id: "456789031",
    date: "2024-09-22",
    time: "09:15",
    amt: "-$120.75",
    status: "Completed",
    note: "Monthly utility bill"
  }, {
    name: "Online Subscription",
    cat: "Services",
    icon: "repeat",
    tone: "expense",
    acct: "Platinum Plus Visa",
    brand: "VISA",
    id: "456789040",
    date: "2024-09-18",
    time: "08:00",
    amt: "-$12.99",
    status: "Pending",
    note: "Streaming service renewal"
  }, {
    name: "Stock Dividend",
    cat: "Investments",
    icon: "chart-line",
    tone: "income",
    acct: "Freedom Mastercard",
    brand: "MC",
    id: "456789058",
    date: "2024-09-17",
    time: "11:20",
    amt: "+$300.00",
    status: "Completed",
    note: "Quarterly stock dividend"
  }, {
    name: "Armani Exchange",
    cat: "Shopping",
    icon: "shopping-bag",
    tone: "expense",
    acct: "Platinum Plus Visa",
    brand: "VISA",
    id: "456789065",
    date: "2024-09-16",
    time: "18:45",
    amt: "-$96.11",
    status: "Pending",
    note: "Purchased clothing"
  }],
  cards: [{
    label: "Personal",
    brand: "VISA",
    last4: "8744",
    tone: "blue"
  }, {
    label: "Business",
    brand: "VISA",
    last4: "5641",
    tone: "gray"
  }, {
    label: "Business",
    brand: "GB",
    last4: "9007",
    tone: "ink"
  }],
  walletBalance: [22, 26, 24, 31, 29, 38, 34, 44, 40, 52, 48, 61, 57, 68, 64, 74, 70, 82, 78, 88, 84, 92, 88, 96],
  cashflowLine: [30, 42, 36, 52, 44, 60, 54, 68, 62, 76, 70, 84],
  cashflowLine2: [24, 30, 28, 38, 34, 44, 40, 50, 46, 58, 52, 64],
  aiPrompts: [{
    icon: "activity",
    title: "Track Cash Flow",
    body: "View income, spending, and savings in real time with AI insights for smarter financial management."
  }, {
    icon: "shield-alert",
    title: "Detect Unusual Transactions",
    body: "Get instant alerts on suspicious charges or duplicate payments, keeping your finances fully protected."
  }, {
    icon: "target",
    title: "Plan a Savings Goal",
    body: "Set smart AI-recommended savings targets to securely fund your emergency needs or dream vacation goals."
  }, {
    icon: "gauge",
    title: "Financial Health Score",
    body: "Receive a personalised AI-powered score that evaluates your spending, saving, and investments for financial wellness."
  }],
  expenseTotals: [{
    label: "Daily",
    value: "Rp31.000"
  }, {
    label: "Weekly",
    value: "Rp251.000"
  }, {
    label: "Monthly",
    value: "Rp915.200"
  }]
};
window.FX = FX;
window.MONTHS = MONTHS;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/dashboard/data.js", error: String((e && e.message) || e) }); }

__ds_ns.Avatar = __ds_scope.Avatar;

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.Button = __ds_scope.Button;

__ds_ns.Icon = __ds_scope.Icon;

__ds_ns.IconButton = __ds_scope.IconButton;

__ds_ns.ProgressBar = __ds_scope.ProgressBar;

__ds_ns.Switch = __ds_scope.Switch;

__ds_ns.Tabs = __ds_scope.Tabs;

__ds_ns.AreaChart = __ds_scope.AreaChart;

__ds_ns.Sparkline = __ds_scope.Sparkline;

__ds_ns.BarChart = __ds_scope.BarChart;

__ds_ns.ChartLegend = __ds_scope.ChartLegend;

__ds_ns.DataTable = __ds_scope.DataTable;

__ds_ns.TransactionCell = __ds_scope.TransactionCell;

__ds_ns.Amount = __ds_scope.Amount;

__ds_ns.DonutChart = __ds_scope.DonutChart;

__ds_ns.CategoryLegend = __ds_scope.CategoryLegend;

__ds_ns.AssistantOrb = __ds_scope.AssistantOrb;

__ds_ns.AssistantComposer = __ds_scope.AssistantComposer;

__ds_ns.CreditCardItem = __ds_scope.CreditCardItem;

__ds_ns.ActionTile = __ds_scope.ActionTile;

__ds_ns.ExchangePanel = __ds_scope.ExchangePanel;

__ds_ns.SuggestionCard = __ds_scope.SuggestionCard;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.Input = __ds_scope.Input;

__ds_ns.Radio = __ds_scope.Radio;

__ds_ns.SearchField = __ds_scope.SearchField;

__ds_ns.Select = __ds_scope.Select;

__ds_ns.AccountSwitcher = __ds_scope.AccountSwitcher;

__ds_ns.SidebarNav = __ds_scope.SidebarNav;

__ds_ns.TopBar = __ds_scope.TopBar;

__ds_ns.BalanceCard = __ds_scope.BalanceCard;

__ds_ns.Card = __ds_scope.Card;

__ds_ns.CardFooterLink = __ds_scope.CardFooterLink;

__ds_ns.MetricCard = __ds_scope.MetricCard;

__ds_ns.StatTile = __ds_scope.StatTile;

__ds_ns.UpgradeCard = __ds_scope.UpgradeCard;

})();
