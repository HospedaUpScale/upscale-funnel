
  var currentWsId = 2;
  var currentRole = 'super_admin';
  var allOrdersData = [];

  function getAdminToken() {
    return localStorage.getItem('upscale_admin_token') || 'super_admin_sincero_secret_2026';
  }

  function abrirModalAuth() {
    var modal = document.getElementById('modal-auth');
    document.getElementById('custom-token-input').value = getAdminToken();
    if (modal.showModal) modal.showModal();
    else modal.style.display = 'block';
  }

  function fecharModalAuth() {
    var modal = document.getElementById('modal-auth');
    if (modal.close) modal.close();
    else modal.style.display = 'none';
  }

  function definirTokenRapido(t) {
    localStorage.setItem('upscale_admin_token', t);
    fecharModalAuth();
    atualizarTudo();
  }

  function salvarTokenManual() {
    var t = document.getElementById('custom-token-input').value.trim();
    if (!t) return;
    localStorage.setItem('upscale_admin_token', t);
    fecharModalAuth();
    atualizarTudo();
  }

  function copiarValor(elId) {
    var el = document.getElementById(elId);
    var val = el.value || el.textContent;
    navigator.clipboard.writeText(val).then(function() {
      alert('Copiado com sucesso: ' + val);
    });
  }

  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebar-backdrop').classList.toggle('show');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-backdrop').classList.remove('show');
  }

  function navigate(viewId) {
    document.querySelectorAll('.view-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item-btn').forEach(b => b.classList.remove('active'));

    var pane = document.getElementById('view-' + viewId);
    if (pane) pane.classList.add('active');

    // Marca botão ativo na sidebar
    event.currentTarget.classList.add('active');
    closeSidebar();

    var titles = {
      dash: { t: "Visão Geral da Rede", s: "Controle unificado de transações Pix, split inteligente e injeção de upsell.", bc: "DASHBOARD" },
      tx: { t: "Transações Pix & Liquidação", s: "Extrato analítico em tempo real e conciliação de cobranças compensadas.", bc: "TRANSAÇÕES" },
      campaigns: { t: "Campanhas & Inventário de Anúncios", s: "Gerenciamento do ciclo de vida, publicação e blocos da oferta de booster.", bc: "CAMPANHAS" },
      partners: { t: "Rede de Parceiros White-Label", s: "Acompanhamento consolidado de faturamento, subcontas e comissionamento.", bc: "PARCEIROS" },
      gateway: { t: "API Gateway & Conexão", s: "Credenciais de webhook, endpoints e snippets para homologar qualquer rifa.", bc: "GATEWAY" },
      simulator: { t: "Simulador Mobile do Comprador", s: "Ambiente interativo de teste da raspadinha e checkout Pix em 1 clique.", bc: "SIMULADOR" },
      logs: { t: "Auditoria de Logs & Webhooks", s: "Monitoramento e rastreabilidade contínua de eventos de API e conversão.", bc: "AUDITORIA" }
    };
    if (titles[viewId]) {
      document.getElementById('page-title').textContent = titles[viewId].t;
      document.getElementById('page-subtitle').textContent = titles[viewId].s;
      var bc = document.getElementById('breadcrumb-current');
      if (bc) bc.textContent = titles[viewId].bc;
    }

    if (viewId === 'dash' || viewId === 'tx') carregarMetricas();
    if (viewId === 'campaigns') { carregarConfiguracaoOferta(); carregarCampanhas(); }
    if (viewId === 'partners') carregarParceiro();
    if (viewId === 'logs') carregarLogs();
  }

  function trocarWorkspace() {
    currentWsId = parseInt(document.getElementById('workspace-select').value);
    var sel = document.getElementById('workspace-select');
    var selectedTxt = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : 'Workspace';
    var cleanName = selectedTxt.replace(/^\[.*?\]\s*/, '');
    var accName = document.getElementById('account-name-text');
    var accSub = document.getElementById('account-sub-text');
    var accAv = document.getElementById('account-avatar-text');
    if (accName) accName.textContent = cleanName;
    if (accSub) accSub.textContent = currentWsId === 0 ? 'Visão Consolidada Global' : ('Workspace Ativo • #' + currentWsId);
    if (accAv) accAv.textContent = (cleanName.substring(0, 2)).toUpperCase();

    carregarMetricas();
    carregarConfiguracaoOferta();
    carregarCampanhas();
  }

  function carregarStatusAutenticacao() {
    var token = getAdminToken();
    fetch('../api/admin/auth-status.php', {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status !== 'success') {
        abrirModalAuth();
        return;
      }

      var auth = data.auth;
      currentRole = auth.role;

      // 1. Atualizar Badge no Topbar e Account Switcher na Sidebar
      var badgeTag = document.getElementById('role-tag');
      var badgeIcon = document.getElementById('role-icon');
      var badgeName = document.getElementById('role-name');
      var badgeDesc = document.getElementById('role-level-desc');

      var accName = document.getElementById('account-name-text');
      var accSub = document.getElementById('account-sub-text');
      var accAvatar = document.getElementById('account-avatar-text');

      if (auth.role === 'super_admin') {
        badgeIcon.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>';
        badgeName.textContent = 'upScale Master';
        if (badgeDesc) badgeDesc.textContent = 'Controle Total';
        badgeTag.textContent = 'GLOBAL';
        badgeTag.className = 'role-tag';

        if (accName) accName.textContent = 'upScale Media HQ';
        if (accSub) accSub.textContent = 'Acesso Global Master';
        if (accAvatar) accAvatar.textContent = 'US';
      } else if (auth.role === 'partner_admin') {
        badgeIcon.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="m11 17 2 2a1 1 0 0 0 1.4 0l4.3-4.3a1 1 0 0 0 0-1.4l-2.6-2.6a1 1 0 0 0-1.4 0l-1.4 1.4"/><path d="m13 7-2-2a1 1 0 0 0-1.4 0L5.3 9.3a1 1 0 0 0 0 1.4l2.6 2.6a1 1 0 0 0 1.4 0l1.4-1.4"/><path d="m18 11 3 3"/><path d="m3 13 3-3"/></svg>';
        badgeName.textContent = auth.workspace.name;
        if (badgeDesc) badgeDesc.textContent = 'Gestão de Subcontas';
        badgeTag.textContent = 'PARCEIRO';
        badgeTag.className = 'role-tag partner';

        if (accName) accName.textContent = auth.workspace.name;
        if (accSub) accSub.textContent = 'Parceiro White-Label';
        if (accAvatar) accAvatar.textContent = (auth.workspace.name.substring(0, 2)).toUpperCase();
      } else {
        badgeIcon.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="m2 7 4.4-4.4A2 2 0 0 1 7.8 2h8.4a2 2 0 0 1 1.4.6L22 7v13a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7z"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';
        badgeName.textContent = auth.workspace.name;
        if (badgeDesc) badgeDesc.textContent = 'Campanhas da Rifa';
        badgeTag.textContent = 'SUBCONTA';
        badgeTag.className = 'role-tag merchant';

        if (accName) accName.textContent = auth.workspace.name;
        if (accSub) accSub.textContent = 'Organizador da Rifa';
        if (accAvatar) accAvatar.textContent = (auth.workspace.name.substring(0, 2)).toUpperCase();
      }

      // 2. Adaptação Dinâmica da Sidebar
      var liPartners = document.getElementById('nav-li-partners');
      var txtCampaigns = document.getElementById('nav-text-campaigns');
      var txtPartners = document.getElementById('nav-text-partners');

      if (auth.role === 'merchant_admin') {
        if (liPartners) liPartners.style.display = 'none';
        if (txtCampaigns) txtCampaigns.textContent = 'Minha Oferta & Campanha';
      } else if (auth.role === 'partner_admin') {
        if (liPartners) liPartners.style.display = 'block';
        if (txtPartners) txtPartners.textContent = 'Minhas Subcontas & Comissões';
        if (txtCampaigns) txtCampaigns.textContent = 'Campanhas & Inventário';
      } else {
        if (liPartners) liPartners.style.display = 'block';
        if (txtPartners) txtPartners.textContent = 'Rede de Parceiros (Split)';
        if (txtCampaigns) txtCampaigns.textContent = 'Campanhas & Inventário';
      }

      // 3. Preenchimento Estrito do Seletor de Workspaces
      var sel = document.getElementById('workspace-select');
      var prevVal = sel.value;
      sel.innerHTML = '';

      if (auth.role === 'super_admin') {
        var optGlobal = document.createElement('option');
        optGlobal.value = "0";
        optGlobal.textContent = "[Global] upScale Master (Todas as Rifas)";
        sel.appendChild(optGlobal);
      }

      auth.accessible_workspaces.forEach(w => {
        var opt = document.createElement('option');
        opt.value = w.id;
        var prefix = w.type === 'partner_whitelabel' ? '[Parceiro] ' : (w.is_subaccount ? '[Subconta] ' : '[Rifa] ');
        opt.textContent = prefix + w.name + (w.slug ? ' (' + w.slug + ')' : '');
        sel.appendChild(opt);
      });

      if (prevVal && sel.querySelector(`option[value="${prevVal}"]`)) {
        sel.value = prevVal;
        currentWsId = parseInt(prevVal);
      } else if (sel.options.length) {
        sel.selectedIndex = 0;
        currentWsId = parseInt(sel.value);
      }

      document.getElementById('conn-error-banner').classList.remove('show');
      carregarMetricas();
      carregarConfiguracaoOferta();
      carregarCampanhas();
      if (auth.role !== 'merchant_admin') {
        carregarParceiro();
      }
    })
    .catch(e => {
      console.error('Erro ao verificar auth:', e);
      mostrarErroConexao();
    });
  }

  function mostrarErroConexao() {
    document.getElementById('conn-error-banner').classList.add('show');

    ['kpi-recovered','kpi-paid-orders','kpi-aov','kpi-conversion'].forEach(id => {
      var el = document.getElementById(id);
      if (el) el.textContent = '—';
    });
    document.getElementById('kpi-total-orders').textContent = 'sem conexão com a API';

    var errIcon = '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

    var tbodyDash = document.getElementById('dash-orders-body');
    if (tbodyDash) {
      tbodyDash.innerHTML = '<tr><td colspan="7"><div class="empty-state is-error">' + errIcon +
        '<strong>Não foi possível carregar as transações</strong>' +
        '<span>Confira sua conexão ou token de acesso e tente novamente.</span></div></td></tr>';
    }
    var tbodyFull = document.getElementById('full-orders-body');
    if (tbodyFull) {
      tbodyFull.innerHTML = '<tr><td colspan="8"><div class="empty-state is-error">' + errIcon +
        '<strong>Não foi possível carregar o extrato</strong>' +
        '<span>Confira sua conexão ou token de acesso e tente novamente.</span></div></td></tr>';
    }
    var chartWrap = document.getElementById('chart-bars-wrap');
    if (chartWrap) {
      chartWrap.style.display = 'block';
      chartWrap.innerHTML = '<div class="empty-state is-error" style="padding:20px;">' + errIcon +
        '<strong>Gráfico indisponível</strong><span>Não foi possível buscar o volume transacionado.</span></div>';
    }
  }

  function carregarMetricas() {
    var token = getAdminToken();
    fetch('../api/admin/metrics.php?workspace_id=' + currentWsId, {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status !== 'success') { mostrarErroConexao(); return; }
      document.getElementById('conn-error-banner').classList.remove('show');
      var m = data.metrics;
      allOrdersData = data.recent_orders || [];

      // KPIs
      document.getElementById('kpi-recovered').textContent = m.total_recovered.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      document.getElementById('kpi-paid-orders').textContent = m.paid_orders;
      document.getElementById('kpi-total-orders').textContent = 'de ' + m.total_orders + ' gerados na API';
      document.getElementById('kpi-aov').textContent = m.average_ticket.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      document.getElementById('kpi-conversion').textContent = m.conversion_rate + '%';

      // Funil
      if (m.funnel) {
        document.getElementById('funnel-views').textContent = m.funnel.views || 0;
        document.getElementById('funnel-scratches').textContent = m.funnel.scratches || 0;
        document.getElementById('funnel-pix').textContent = m.funnel.pix_generated || m.total_orders;
        document.getElementById('funnel-paid').textContent = m.funnel.pix_paid || m.paid_orders;
      }

      // Splits na Balance Card
      var saasVal = (m.total_recovered * 0.10).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      var partVal = (m.total_recovered * 0.05).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      document.getElementById('kpi-split-saas').textContent = saasVal;
      document.getElementById('kpi-split-partner').textContent = partVal;

      // Renderiza tabelas
      renderizarTabelaDash(allOrdersData);
      renderizarTabelaFull(allOrdersData);
      renderizarGraficoBarras(m.paid_orders, m.total_orders);
    })
    .catch(e => { console.error(e); mostrarErroConexao(); });
  }

  function renderizarTabelaDash(orders) {
    var tbody = document.getElementById('dash-orders-body');
    if (!orders || !orders.length) {
      tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg><strong>Nenhuma transação ainda</strong><span>Assim que o primeiro Pix for gerado, ele aparece aqui em tempo real. Enquanto isso, teste no Simulador Mobile.</span></div></td></tr>';
      return;
    }
    tbody.innerHTML = orders.slice(0, 6).map(o => {
      var badgeClass = o.status === 'paid' ? 'badge-paid' : (o.status === 'expired' ? 'badge-expired' : 'badge-pending');
      var statusLabel = o.status === 'paid' ? '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polyline points="20 6 9 17 4 12"/></svg> Pago' : (o.status === 'expired' ? 'Expirado' : 'Aguardando Pagamento');
      var forceBtn = o.status !== 'paid'
        ? `<button class="btn-action-small" onclick="forcarPorId(${o.id})"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Confirmar</button>`
        : `<span style="color:var(--green-700); font-size:11px; font-weight:700;">Liquidado</span>`;

      var valFmt = (parseFloat(o.upsell_amount) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

      return `<tr>
        <td class="fx-num">#${o.id}</td>
        <td style="font-size:12px; color:var(--gray-500);">${o.created_at}</td>
        <td><strong>${escapeHtml(o.customer_name || 'Comprador Anônimo')}</strong></td>
        <td class="fx-num" style="color:var(--gray-500);">${escapeHtml(o.customer_phone || '-')}</td>
        <td class="fx-num" style="font-weight:700; color:var(--ink-900);">${valFmt}</td>
        <td><span class="badge-status ${badgeClass}">${statusLabel}</span></td>
        <td>${forceBtn}</td>
      </tr>`;
    }).join('');
  }

  function renderizarTabelaFull(orders) {
    var tbody = document.getElementById('full-orders-body');
    if (!orders || !orders.length) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg><strong>Nenhuma cobrança Pix encontrada</strong><span>Ajuste a busca ou aguarde novas transações da rede.</span></div></td></tr>';
      return;
    }
    tbody.innerHTML = orders.map(o => {
      var badgeClass = o.status === 'paid' ? 'badge-paid' : (o.status === 'expired' ? 'badge-expired' : 'badge-pending');
      var statusLabel = o.status === 'paid' ? '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polyline points="20 6 9 17 4 12"/></svg> Liquidado no Banco' : (o.status === 'expired' ? 'Expirado' : 'Aguardando Pagador');
      var val = parseFloat(o.upsell_amount) || 0;
      var saasPart = (val * 0.10).toFixed(2);
      var partnerPart = (val * 0.05).toFixed(2);
      var netPart = (val * 0.85).toFixed(2);
      var splitInfo = `R$ ${saasPart} / R$ ${partnerPart} / R$ ${netPart}`;
      var valFmt = val.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

      var action = o.status !== 'paid'
        ? `<button class="btn-action-small" onclick="forcarPorId(${o.id})"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Forçar Pagamento</button>`
        : `<span style="color:var(--green-700); font-size:11px; font-weight:700;">BACEN OK</span>`;

      return `<tr>
        <td class="fx-num">#${o.id}</td>
        <td style="font-size:12px; color:var(--gray-500);">${o.created_at}</td>
        <td><strong>${escapeHtml(o.customer_name || 'Comprador')}</strong></td>
        <td class="fx-num">${escapeHtml(o.customer_phone || '-')}</td>
        <td class="fx-num" style="font-weight:700;">${valFmt}</td>
        <td><span class="badge-status ${badgeClass}">${statusLabel}</span></td>
        <td class="fx-num" style="font-size:12px; color:var(--gray-600);">${splitInfo}</td>
        <td>${action}</td>
      </tr>`;
    }).join('');
  }

  function filtrarTransacoes() {
    var q = document.getElementById('tx-search').value.toLowerCase().trim();
    if (!q) {
      renderizarTabelaFull(allOrdersData);
      return;
    }
    var filtered = allOrdersData.filter(o => 
      (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
      (o.customer_phone && o.customer_phone.includes(q)) ||
      String(o.id).includes(q)
    );
    renderizarTabelaFull(filtered);
  }

  function renderizarGraficoBarras(pagos, total) {
    var wrap = document.getElementById('chart-bars-wrap');
    var meses = ["Mar 08", "Mar 09", "Mar 10", "Mar 11", "Mar 12", "Mar 13", "Hoje (Ao Vivo)"];
    var alturas = [30, 45, 25, 60, 50, 75, Math.max(20, Math.min(100, (pagos + 1) * 18))];

    wrap.innerHTML = meses.map((m, i) => {
      var isHoje = i === meses.length - 1;
      var bg = isHoje ? 'var(--blue-600)' : 'var(--blue-100)';
      return `<div style="flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end;">
        <div style="width:100%; max-width:32px; height:${alturas[i]}%; background:${bg}; border-radius:4px 4px 0 0; transition:height .3s;"></div>
        <span style="font-size:10px; color:var(--gray-500); margin-top:6px; white-space:nowrap;">${m}</span>
      </div>`;
    }).join('');
  }

  /* ═══ CARREGAR E SALVAR CONFIGURAÇÕES COMPLETAS DA OFERTA (5 BLOCOS) ═══ */
  function carregarConfiguracaoOferta() {
    var token = getAdminToken();
    var ws = currentWsId || 2;
    fetch('../api/admin/offer.php?workspace_id=' + ws, {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status !== 'success' || !data.offer) return;
      var off = data.offer;
      var th = off.theme || {};

      // Bloco 1: Identidade & Textos
      document.getElementById('camp-title').value = off.title || 'Booster 110X';
      document.getElementById('camp-subtitle').value = off.subtitle || '';
      document.getElementById('camp-eyebrow').value = th.eyebrow || 'OFERTA EXCLUSIVA DESBLOQUEADA';
      document.getElementById('camp-scratch-top').value = th.scratch_inst_top || 'RASPE COM O DEDO';
      document.getElementById('camp-scratch-sub').value = th.scratch_inst_sub || 'REVELE SUA OFERTA EXCLUSIVA';
      document.getElementById('camp-cta-label').value = th.cta_label || 'ATIVAR MEU BOOSTER 110X';
      document.getElementById('camp-cta-subtext').value = th.cta_subtext || 'Liberação imediata via Pix em 1 clique';

      // Bloco 2: Valores & Cotas
      document.getElementById('camp-price').value = off.price || 90.00;
      document.getElementById('camp-anchor-price').value = th.anchor_price || 180.00;
      document.getElementById('camp-quotas').value = off.quota_count || 110;
      document.getElementById('camp-timer-min').value = th.timer_minutes || 3;
      document.getElementById('camp-thresh').value = th.scratch_threshold || 22;

      // Bloco 3: Mídia & Vturb
      document.getElementById('camp-vturb-player').value = off.vturb_player_id || '';
      document.getElementById('camp-vturb-account').value = off.vturb_account_id || '';
      document.getElementById('camp-pitch-delay').value = th.pitch_delay || 0;
      document.getElementById('camp-video-src').value = th.video_src || '';

      // Bloco 4: Cores
      var pCol = th.primary_color || '#0229C4';
      var aCol = th.accent_color || '#9FE870';
      var fStart = th.foil_start || '#021F96';
      var fEnd = th.foil_end || '#3A5BE8';

      document.getElementById('camp-color-primary').value = pCol;
      document.getElementById('pick-color-primary').value = pCol;
      document.getElementById('camp-color-accent').value = aCol;
      document.getElementById('pick-color-accent').value = aCol;
      document.getElementById('camp-foil-start').value = fStart;
      document.getElementById('pick-foil-start').value = fStart;
      document.getElementById('camp-foil-end').value = fEnd;
      document.getElementById('pick-foil-end').value = fEnd;

      // Bloco 5: Checkout & Links
      document.getElementById('camp-checkout-mode').value = off.checkout_mode || 'pix_native';
      document.getElementById('camp-legacy-url').value = off.legacy_checkout_url || '';
    })
    .catch(e => console.error('Erro ao carregar oferta:', e));
  }

  function salvarConfiguracaoOferta() {
    var token = getAdminToken();
    var btn = document.getElementById('btn-save-offer');
    btn.disabled = true;
    btn.textContent = 'Salvando Alterações...';

    var payload = {
      workspace_id: currentWsId || 2,
      title: document.getElementById('camp-title').value.trim(),
      subtitle: document.getElementById('camp-subtitle').value.trim(),
      price: parseFloat(document.getElementById('camp-price').value) || 90.00,
      quota_count: parseInt(document.getElementById('camp-quotas').value) || 110,
      vturb_player_id: document.getElementById('camp-vturb-player').value.trim(),
      vturb_account_id: document.getElementById('camp-vturb-account').value.trim(),
      legacy_checkout_url: document.getElementById('camp-legacy-url').value.trim(),
      checkout_mode: document.getElementById('camp-checkout-mode').value,
      theme: {
        eyebrow: document.getElementById('camp-eyebrow').value.trim(),
        scratch_inst_top: document.getElementById('camp-scratch-top').value.trim(),
        scratch_inst_sub: document.getElementById('camp-scratch-sub').value.trim(),
        cta_label: document.getElementById('camp-cta-label').value.trim(),
        cta_subtext: document.getElementById('camp-cta-subtext').value.trim(),
        anchor_price: parseFloat(document.getElementById('camp-anchor-price').value) || 180.00,
        timer_minutes: parseFloat(document.getElementById('camp-timer-min').value) || 3,
        scratch_threshold: parseInt(document.getElementById('camp-thresh').value) || 22,
        pitch_delay: parseInt(document.getElementById('camp-pitch-delay').value) || 0,
        video_src: document.getElementById('camp-video-src').value.trim(),
        primary_color: document.getElementById('camp-color-primary').value.trim(),
        accent_color: document.getElementById('camp-color-accent').value.trim(),
        foil_start: document.getElementById('camp-foil-start').value.trim(),
        foil_end: document.getElementById('camp-foil-end').value.trim(),
      }
    };

    fetch('../api/admin/offer.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar Todas as Alterações da Oferta';
      if (data.status === 'success') {
        alert('Sucesso! Todas as alterações de blocos, cores, textos, links e booster foram salvas no motor upScale!');
        var iframe = document.getElementById('sim-iframe');
        if (iframe) iframe.src = iframe.src;
        carregarCampanhas();
      } else {
        alert('Erro ao salvar oferta: ' + (data.message || 'Falha'));
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar Todas as Alterações da Oferta';
      alert('Erro de conexão ao salvar configurações.');
    });
  }

  // ============================================================================
  // GESTÃO DE AÇÕES & CAMPANHAS DE RIFAS (PUBLICAR, FINALIZAR E CRIAR)
  // ============================================================================
  var allCampaignsData = [];
  var selectedCampaignId = null;

  function carregarCampanhas() {
    var token = getAdminToken();
    var targetWs = currentWsId || 2;
    fetch('../api/admin/campaigns.php?workspace_id=' + targetWs, {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status !== 'success') return;
      allCampaignsData = data.campaigns || [];
      renderizarCampanhas();
    })
    .catch(e => console.error('Erro ao carregar campanhas:', e));
  }

  function renderizarCampanhas() {
    var select = document.getElementById('action-select-dropdown');
    var tbody = document.getElementById('actions-table-body');
    var countLabel = document.getElementById('actions-count-label');
    
    if (!allCampaignsData.length) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:var(--gray-400);">Nenhuma ação cadastrada. Clique em "+ Nova Ação" acima.</td></tr>';
      if (countLabel) countLabel.textContent = '0 ações';
      return;
    }

    if (countLabel) countLabel.textContent = allCampaignsData.length + (allCampaignsData.length === 1 ? ' ação cadastrada' : ' ações cadastradas');

    // Se nenhuma estiver selecionada, seleciona a primeira
    if (!selectedCampaignId || !allCampaignsData.find(c => c.id === selectedCampaignId)) {
      selectedCampaignId = allCampaignsData[0].id;
    }

    // Preenche o dropdown de seleção
    if (select) {
      select.innerHTML = '';
      allCampaignsData.forEach(c => {
        var opt = document.createElement('option');
        opt.value = c.id;
        var statusTxt = c.status === 'published' ? '[NO AR]' : (c.status === 'ended' ? '[FINALIZADA]' : '[RASCUNHO]');
        opt.textContent = statusTxt + ' ' + c.title;
        if (c.id === selectedCampaignId) opt.selected = true;
        select.appendChild(opt);
      });
    }

    // Atualiza Hero Card da Ação Selecionada
    var activeCamp = allCampaignsData.find(c => c.id === selectedCampaignId) || allCampaignsData[0];
    atualizarHeroAcao(activeCamp);

    // Renderiza Tabela de Ações
    if (tbody) {
      tbody.innerHTML = allCampaignsData.map(c => {
        var isPub = c.status === 'published';
        var isEnded = c.status === 'ended';

        var badgeHtml = isPub 
          ? '<span class="action-status-badge badge-published"><span class="pulse-online"></span> NO AR</span>'
          : (isEnded 
              ? '<span class="action-status-badge badge-ended"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg> FINALIZADA</span>'
              : '<span class="action-status-badge badge-draft"><span class="pulse-amber"></span> RASCUNHO</span>');

        var actionButtons = '';
        if (!isPub) {
          actionButtons += `<button class="btn-action-publish" style="padding:6px 12px; font-size:11px;" onclick="publicarAcao(${c.id})">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><polyline points="20 6 9 17 4 12"/></svg> Publicar
          </button> `;
        }
        if (!isEnded) {
          actionButtons += `<button class="btn-action-finalize" style="padding:6px 12px; font-size:11px;" onclick="abrirModalFinalizarAcao(${c.id})">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg> Finalizar
          </button> `;
        }
        actionButtons += `<button class="btn-pill-secondary" style="padding:6px 10px; font-size:11px;" onclick="selecionarAcaoPorId(${c.id})">
          Editar Blocos
        </button>`;

        var boosterTxt = c.offer ? (c.offer.title + ' (+' + c.offer.quota_count + ' cotas)') : 'Sem Booster';
        var totalRev = c.metrics && c.metrics.total_volume ? c.metrics.total_volume.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 'R$ 0,00';
        var totalOrders = c.metrics ? c.metrics.total_orders : 0;

        return `<tr>
          <td class="fx-num">#${c.id}</td>
          <td><strong>${escapeHtml(c.title)}</strong></td>
          <td><span style="font-size:11px; background:var(--gray-100); padding:2px 8px; border-radius:4px; font-weight:700; text-transform:uppercase;">${escapeHtml(c.external_platform)}</span></td>
          <td class="fx-num">${(c.ticket_price || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
          <td><strong style="color:var(--blue-600);">${escapeHtml(boosterTxt)}</strong></td>
          <td class="fx-num">${totalOrders}</td>
          <td class="fx-num" style="font-weight:700; color:var(--green-700);">${totalRev}</td>
          <td>${badgeHtml}</td>
          <td><div style="display:flex; gap:6px; align-items:center;">${actionButtons}</div></td>
        </tr>`;
      }).join('');
    }
  }

  function atualizarHeroAcao(camp) {
    if (!camp) return;
    document.getElementById('hero-campaign-title').textContent = camp.title;
    document.getElementById('hero-meta-platform').textContent = camp.external_platform.toUpperCase();
    document.getElementById('hero-meta-price').textContent = (camp.ticket_price || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('hero-meta-booster').textContent = camp.offer ? (camp.offer.quota_count + ' Cotas Extras (R$ ' + (camp.offer.price || 0).toFixed(2) + ')') : '110 Cotas';

    var badgeWrap = document.getElementById('action-status-badge-container');
    var btnPub = document.getElementById('btn-publish-action');
    var btnFin = document.getElementById('btn-finalize-action');
    var statStatus = document.getElementById('hero-stat-status');
    var statOrders = document.getElementById('hero-stat-orders');
    var statRevenue = document.getElementById('hero-stat-revenue');
    var statDate = document.getElementById('hero-stat-date');

    if (camp.status === 'published') {
      badgeWrap.innerHTML = '<span class="action-status-badge badge-published"><span class="pulse-online"></span> NO AR / PUBLICADA</span>';
      statStatus.textContent = 'Monetizando ao Vivo';
      statStatus.style.color = 'var(--green-700)';
      btnPub.style.display = 'none';
      btnFin.style.display = 'inline-flex';
    } else if (camp.status === 'ended') {
      badgeWrap.innerHTML = '<span class="action-status-badge badge-ended"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg> AÇÃO FINALIZADA / CONCLUÍDA</span>';
      statStatus.textContent = 'Sorteio Encerrado';
      statStatus.style.color = 'var(--gray-600)';
      btnPub.style.display = 'inline-flex';
      btnPub.innerHTML = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><polyline points="20 6 9 17 4 12"/></svg> Reabrir / Publicar Ação';
      btnFin.style.display = 'none';
    } else {
      badgeWrap.innerHTML = '<span class="action-status-badge badge-draft"><span class="pulse-amber"></span> RASCUNHO / PAUSADA</span>';
      statStatus.textContent = 'Pausada / Em Ajuste';
      statStatus.style.color = 'var(--amber-600)';
      btnPub.style.display = 'inline-flex';
      btnPub.innerHTML = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><polyline points="20 6 9 17 4 12"/></svg> Publicar Ação';
      btnFin.style.display = 'none';
    }

    if (camp.metrics) {
      statOrders.textContent = camp.metrics.total_orders + ' pedidos';
      statRevenue.textContent = (camp.metrics.total_volume || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    statDate.textContent = camp.ended_at ? ('Encerrada em ' + camp.ended_at.substring(0, 10)) : (camp.published_at ? ('No ar desde ' + camp.published_at.substring(0, 10)) : 'Criada recentemente');

    // Sincroniza o subtítulo no formulário de blocos
    var subInput = document.getElementById('camp-subtitle');
    if (subInput && camp.title) subInput.value = camp.title;
  }

  function selecionarAcaoPorId(id) {
    selectedCampaignId = parseInt(id);
    var activeCamp = allCampaignsData.find(c => c.id === selectedCampaignId);
    if (activeCamp) {
      atualizarHeroAcao(activeCamp);
    }
  }

  function publicarAcaoAtual() {
    if (!selectedCampaignId) return;
    publicarAcao(selectedCampaignId);
  }

  function publicarAcao(campId) {
    var token = getAdminToken();
    var targetWs = currentWsId || 2;
    fetch('../api/admin/campaigns.php?workspace_id=' + targetWs, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
        action: 'publish',
        campaign_id: campId,
        workspace_id: targetWs
      })
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        alert('Ação de rifa publicada com sucesso! O Booster está no ar e pronto para monetizar.');
        carregarCampanhas();
        carregarConfiguracaoOferta();
      } else {
        alert('Erro ao publicar ação: ' + (data.message || 'Falha'));
      }
    })
    .catch(e => {
      console.error(e);
      alert('Erro de conexão ao publicar ação.');
    });
  }

  var pendingFinalizeCampId = null;

  function abrirModalFinalizarAcao(campId) {
    pendingFinalizeCampId = campId ? parseInt(campId) : selectedCampaignId;
    var camp = allCampaignsData.find(c => c.id === pendingFinalizeCampId);
    if (camp) {
      document.getElementById('modal-finalize-camp-title').textContent = camp.title;
    }
    var modal = document.getElementById('modal-finalizar-acao');
    if (modal.showModal) modal.showModal();
    else modal.style.display = 'block';
  }

  function fecharModalFinalizarAcao() {
    var modal = document.getElementById('modal-finalizar-acao');
    if (modal.close) modal.close();
    else modal.style.display = 'none';
  }

  function confirmarFinalizarAcao() {
    if (!pendingFinalizeCampId) return;
    var token = getAdminToken();
    var targetWs = currentWsId || 2;
    var btn = document.getElementById('btn-confirm-finalize');
    btn.disabled = true;

    fetch('../api/admin/campaigns.php?workspace_id=' + targetWs, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
        action: 'finalize',
        campaign_id: pendingFinalizeCampId,
        workspace_id: targetWs
      })
    })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      fecharModalFinalizarAcao();
      if (data.status === 'success') {
        alert('Ação finalizada com sucesso! O sorteio foi concluído e as vendas congeladas.');
        carregarCampanhas();
        carregarConfiguracaoOferta();
      } else {
        alert('Erro ao finalizar ação: ' + (data.message || 'Falha'));
      }
    })
    .catch(e => {
      btn.disabled = false;
      console.error(e);
      alert('Erro de conexão ao finalizar ação.');
    });
  }

  function abrirModalNovaAcao() {
    var modal = document.getElementById('modal-nova-acao');
    if (modal.showModal) modal.showModal();
    else modal.style.display = 'block';
  }

  function fecharModalNovaAcao() {
    var modal = document.getElementById('modal-nova-acao');
    if (modal.close) modal.close();
    else modal.style.display = 'none';
  }

  function salvarNovaAcao(event) {
    event.preventDefault();
    var token = getAdminToken();
    var targetWs = currentWsId || 2;
    var btn = document.getElementById('btn-submit-action');
    btn.disabled = true;

    var payload = {
      action: 'create',
      workspace_id: targetWs,
      title: document.getElementById('new-action-title').value.trim(),
      ticket_price: parseFloat(document.getElementById('new-action-ticket-price').value) || 10.00,
      external_platform: document.getElementById('new-action-platform').value,
      status: document.getElementById('new-action-status').value
    };

    fetch('../api/admin/campaigns.php?workspace_id=' + targetWs, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      if (data.status === 'success') {
        fecharModalNovaAcao();
        alert('Nova ação de rifa criada com sucesso!');
        document.getElementById('form-nova-acao').reset();
        carregarCampanhas();
      } else {
        alert('Erro ao criar ação: ' + (data.message || 'Falha'));
      }
    })
    .catch(e => {
      btn.disabled = false;
      console.error(e);
      alert('Erro de conexão ao criar ação.');
    });
  }

  function carregarParceiro() {
    var token = getAdminToken();
    fetch('../api/admin/partner-metrics.php', {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status !== 'success') return;
      var p = data.summary;
      document.getElementById('partner-gmv').textContent = (p.total_gmv || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      document.getElementById('partner-commission').textContent = (p.total_partner_commission || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      document.getElementById('partner-saas').textContent = (p.total_saas_commission || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      document.getElementById('partner-merchants-net').textContent = (p.total_merchant_payout || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

      var tbody = document.getElementById('table-merchants-body');
      if (!data.merchants || !data.merchants.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:var(--gray-400);">Nenhum lojista encontrado para este parceiro.</td></tr>';
        return;
      }
      tbody.innerHTML = data.merchants.map(m => `<tr>
        <td class="fx-num">#${m.workspace_id}</td>
        <td><strong>${escapeHtml(m.name)}</strong></td>
        <td><code style="font-size:11px; background:var(--gray-50); padding:2px 6px; border-radius:4px;">${m.custom_domain || (m.slug + '.sorteamos')}</code></td>
        <td class="fx-num">${m.paid_orders}</td>
        <td class="fx-num" style="font-weight:700;">${(m.gmv || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
        <td class="fx-num" style="color:var(--blue-600); font-weight:700;">${(m.partner_commission || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
        <td class="fx-num" style="color:var(--green-700); font-weight:700;">${(m.merchant_payout || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
      </tr>`).join('');
    })
    .catch(e => console.error(e));
  }

  function carregarLogs() {
    var token = getAdminToken();
    fetch('../api/admin/logs.php?workspace_id=' + currentWsId, {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
      var tbody = document.getElementById('logs-table-body');
      if (!data.logs || !data.logs.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:var(--gray-400);">Nenhum registro de log encontrado.</td></tr>';
        return;
      }
      tbody.innerHTML = data.logs.slice(0, 50).map(l => `<tr>
        <td style="font-size:11.5px; color:var(--gray-500);">${l.timestamp}</td>
        <td><strong>${escapeHtml(l.comprador || '-')}</strong></td>
        <td class="fx-num">${escapeHtml(l.telefone || '-')}</td>
        <td class="fx-num">#${escapeHtml(l.order_id || '-')}</td>
        <td><span style="font-family:var(--font-accent); font-size:11px; color:var(--blue-600); font-weight:700;">${l.method} ${l.uri}</span></td>
        <td><code style="font-size:10.5px;">${escapeHtml(l.token)}</code></td>
        <td class="fx-num" style="font-size:11px; color:var(--gray-500);">${escapeHtml(l.ip)}</td>
      </tr>`).join('');
    })
    .catch(e => console.error(e));
  }

  function forcarPorId(txId) {
    var token = getAdminToken();
    fetch('../api/admin/force-paid.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ transaction_id: txId })
    })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        alert('<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Pagamento da Transação #' + txId + ' confirmado com sucesso!');
        carregarMetricas();
      } else {
        alert('Erro ao confirmar: ' + (res.message || 'Falha'));
      }
    })
    .catch(err => alert('Erro de conexão ao forçar pagamento.'));
  }

  function forcarPagamentoSimulador() {
    var txId = parseInt(document.getElementById('sim-force-tx').value) || 1;
    forcarPorId(txId);
  }

  function testarHandshake() {
    fetch('../api/index.php?format=json')
    .then(r => r.json())
    .then(data => {
      var el = document.getElementById('handshake-output');
      el.style.display = 'block';
      el.textContent = JSON.stringify(data, null, 2);
    })
    .catch(e => alert('Erro no handshake: ' + e));
  }

  function atualizarTudo() {
    carregarStatusAutenticacao();
  }

  function abrirModalNovaSubconta() {
    document.getElementById('sub-result').style.display = 'none';
    document.getElementById('form-subconta').reset();
    document.getElementById('modal-subconta').showModal();
  }

  function fecharModalNovaSubconta() {
    document.getElementById('modal-subconta').close();
  }

  function salvarNovaSubconta(e) {
    e.preventDefault();
    var token = getAdminToken();
    var btn = document.getElementById('btn-submit-sub');
    btn.disabled = true;
    btn.textContent = 'Criando Subconta...';

    var payload = {
      parent_id: (currentRole === 'partner_admin') ? currentWsId : 1,
      type: 'merchant',
      name: document.getElementById('sub-name').value.trim(),
      slug: document.getElementById('sub-slug').value.trim(),
      custom_domain: document.getElementById('sub-domain').value.trim() || null,
      price: parseFloat(document.getElementById('sub-price').value) || 90.00,
      quota_count: parseInt(document.getElementById('sub-quotas').value) || 110,
    };

    fetch('../api/admin/workspaces.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Gerar Subconta & Chaves';

      if (data.status === 'success') {
        document.getElementById('res-api-token').textContent = data.workspace.api_token;
        document.getElementById('res-admin-token').textContent = data.workspace.admin_token;
        document.getElementById('sub-result').style.display = 'block';
        atualizarTudo();
      } else {
        alert('Erro ao criar subconta: ' + (data.message || 'Falha'));
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.textContent = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Gerar Subconta & Chaves';
      alert('Erro de conexão ao criar subconta.');
    });
  }

  function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  window.addEventListener('load', function() {
    var loc = window.location;
    var host = loc.host;
    var currentPath = loc.pathname.replace(/\/hub\/?.*$/, '');
    var gwUrl = loc.protocol + '//' + host + currentPath + '/api';
    document.getElementById('gw-base-url').value = gwUrl;
    atualizarTudo();
  });
