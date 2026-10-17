(function(window,document){
  'use strict';
  var ready=false,last={},activeTab='content';
  var groups={
    content:['headline_start','headline_highlight','subtitle_text','wheel_heading','wheel_highlight','wheel_subtitle','wheel_spin_label','title','subtitle','price','quota_count','offer_headline_start','offer_headline_highlight','cta_label','cta_subtext','timer_minutes'],
    media:['vturb_embed','video_src'],
    appearance:['page_bg','primary_color','accent_color','card_bg','card_bg_alt','title_from','title_to','text_color','text_secondary','cta_bg','cta_text','badge_bg','badge_text','border_color','foil_start','foil_end','sound_enabled','sound_volume'],
    payment:['checkout_mode','legacy_checkout_url']
  };
  var titles={page:'Blocos da página',content:'Conteúdo',media:'Mídia',appearance:'Aparência',payment:'Pagamento',ai:'IA'};
  function el(tag,cls,text){var n=document.createElement(tag);if(cls)n.className=cls;if(text!==undefined)n.textContent=text;return n;}
  function initialize(){
    if(ready)return;var sidebar=document.querySelector('.elementor-sidebar');if(!sidebar)return;
    var offer=document.getElementById('builder-inline-offer'),config=document.getElementById('builder-experience-panel'),fields=document.getElementById('builder-offer-fields');
    var pageTools=el('div','editor-page-tools');pageTools.id='editor-page-tools';
    Array.from(sidebar.children).forEach(function(child){if(child!==offer&&child!==config)pageTools.append(child);});
    var header=el('div','editor-panel-header');header.append(el('span','editor-panel-eyebrow','PERSONALIZAR'));var heading=el('h2',null,'Conteúdo');heading.id='editor-panel-heading';header.append(heading);
    var tabs=el('div','editor-panel-tabs');tabs.setAttribute('role','tablist');tabs.setAttribute('aria-label','Configurações do editor');
    Object.keys(titles).forEach(function(key){var btn=el('button',null,titles[key]);btn.id='editor-panel-tab-'+key;btn.type='button';btn.setAttribute('role','tab');btn.onclick=function(){selectTab(key);};tabs.append(btn);});
    var scroll=el('div','editor-form-scroll');scroll.id='editor-form-scroll';
    var settings=el('details','editor-campaign-settings');settings.id='editor-campaign-settings';settings.append(el('summary',null,'Campanha e tipo de funil'),config);scroll.append(settings);
    var summary=offer.querySelector('summary');summary.hidden=true;offer.open=true;offer.classList.add('editor-offer-form');offer.removeAttribute('style');
    Array.from(offer.querySelectorAll(':scope > p')).forEach(function(p){if(p.id!=='builder-offer-status')p.remove();});
    var oldActions=offer.querySelector(':scope > div');if(oldActions)oldActions.remove();
    Object.keys(groups).forEach(function(key){var panel=el('section','editor-field-panel');panel.id='editor-field-panel-'+key;panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby','editor-panel-tab-'+key);
      groups[key].forEach(function(field){var input=document.getElementById('inline-offer-'+field);if(!input)return;var label=document.querySelector('label[for="'+input.id+'"]'),row=el('div','editor-field-row');row.dataset.field=field;
        row.dataset.screen=['headline_start','headline_highlight','subtitle_text'].includes(field)?'scratch':field.startsWith('wheel_')?'wheel':key==='content'?'offer':'all';
        if(label){label.removeAttribute('style');row.append(label);}if(input.type==='checkbox')row.classList.add('editor-field-check');row.append(input);
        if(key==='appearance'&&input.type==='text'&&!['badge_bg','border_color'].includes(field)){var picker=el('input');picker.type='color';picker.setAttribute('aria-label','Escolher '+(label?label.textContent:field));picker.dataset.colorField=field;picker.oninput=function(){input.value=picker.value;input.dispatchEvent(new Event('input',{bubbles:true}));};row.append(picker);}
        panel.append(row);
      });fields.append(panel);
    });
    scroll.append(offer);buildAI(scroll);var notice=el('p','editor-shared-notice','Os dados da oferta são compartilhados pelas páginas desta campanha.');scroll.append(notice);
    var footer=el('div','editor-panel-footer'),save=el('button','btn-primary','Salvar rascunho'),pages=el('button','btn-secondary','Páginas');save.type=pages.type='button';save.id='editor-save-draft';save.onclick=async function(){save.disabled=true;try{await window.salvarFunilRascunho();}finally{save.disabled=false;}};pages.onclick=function(){window.togglePagesDrawer(true);};footer.append(save,pages);
    sidebar.replaceChildren(header,tabs,scroll,pageTools,footer);ready=true;render();
  }
  function selectTab(key){initialize();activeTab=key;render();if(key==='ai')loadAI();}
  function render(){
    if(!ready)return;
    var native=last.target==='experience',shell=document.querySelector('.elementor-shell');if(shell)shell.classList.toggle('editor-native-mode',native);
    if(native&&activeTab==='page')activeTab='content';
    document.getElementById('editor-panel-tab-page').hidden=native;
    var page=activeTab==='page';document.getElementById('editor-page-tools').hidden=!page;document.getElementById('editor-form-scroll').hidden=page;
    var screen=last.stage==='scratch'?last.mechanic:'offer';
    var heading=page?'Blocos da página':activeTab==='content'?(screen==='wheel'?'Conteúdo da roleta':screen==='scratch'?'Conteúdo da raspadinha':'Conteúdo da oferta'):titles[activeTab];
    document.getElementById('editor-panel-heading').textContent=heading;
    Object.keys(titles).forEach(function(key){document.getElementById('editor-panel-tab-'+key).setAttribute('aria-selected',String(activeTab===key));});
    Object.keys(groups).forEach(function(key){var panel=document.getElementById('editor-field-panel-'+key);panel.hidden=activeTab!==key;panel.querySelectorAll('.editor-field-row').forEach(function(row){row.hidden=key==='content'&&row.dataset.screen!==screen;});});
    document.querySelectorAll('[data-color-field]').forEach(function(picker){var value=document.getElementById('inline-offer-'+picker.dataset.colorField).value;if(/^#[0-9a-f]{6}$/i.test(value))picker.value=value;});
    document.getElementById('builder-inline-offer').open=true;document.getElementById('editor-ai-panel').hidden=activeTab!=='ai';
    // A link is relevant only for an external checkout.
    var external=document.getElementById('inline-offer-checkout_mode').value==='external_redirect';
    document.querySelector('[data-field="legacy_checkout_url"]').hidden=!external;
  }
  function update(state){
    initialize();if(state.pageId!==last.pageId)clearAI();var changed=state.target!==last.target||state.stage!==last.stage||state.mechanic!==last.mechanic;
    var editing=!!document.activeElement.closest('#builder-offer-fields');
    if(changed&&!editing)activeTab=state.target==='page'?'page':state.stage==='checkout'?'payment':'content';
    last=state;render();
  }
  var aiSuggestion=null,aiBusy=false,aiAvailable=false;
  var copyFields={title:'Título da oferta',subtitle:'Subtítulo',offer_headline_start:'Título da tela final',offer_headline_highlight:'Destaque da tela final',cta_label:'Texto do botão',cta_subtext:'Legenda do botão'};
  function aiScope(){return String(window.currentWsId)+':'+document.getElementById('builder-flow-campaign').value+':'+last.pageId;}
  function aiUrl(){return '../api/admin/ai-copy.php?workspace_id='+encodeURIComponent(window.currentWsId)+'&campaign_id='+encodeURIComponent(document.getElementById('builder-flow-campaign').value||0);}
  function aiStatus(text){document.getElementById('editor-ai-status').textContent=text;}
  function clearAI(){aiSuggestion=null;document.getElementById('editor-ai-result').replaceChildren();document.getElementById('editor-ai-apply').hidden=true;}
  function buildAI(scroll){
    var panel=el('section','editor-field-panel');panel.id='editor-ai-panel';panel.hidden=true;
    panel.append(el('h3',null,'Gerar copys com Claude'),el('p',null,'Descreva o público, o tom e o que deseja melhorar. Revise a sugestão antes de aplicar à oferta.'));
    var label=el('label',null,'O que deseja criar?');label.htmlFor='editor-ai-brief';var brief=el('textarea','form-input');brief.id='editor-ai-brief';brief.rows=5;brief.maxLength=2500;brief.placeholder='Ex.: uma copy mais direta para apresentar o Booster, sem prometer ganhos.';
    var status=el('p');status.id='editor-ai-status';status.setAttribute('role','status');
    var generate=el('button','btn-primary','Gerar novas copys');generate.type='button';generate.id='editor-ai-generate';generate.onclick=generateAI;
    var result=el('dl');result.id='editor-ai-result';var apply=el('button','btn-secondary','Aplicar à oferta');apply.type='button';apply.id='editor-ai-apply';apply.hidden=true;
    apply.onclick=function(){if(!aiSuggestion||aiSuggestion.scope!==aiScope()){clearAI();return;}Object.keys(copyFields).forEach(function(k){document.getElementById('inline-offer-'+k).value=aiSuggestion.values[k];});window.atualizarOfertaInline('title');clearAI();selectTab('content');};
    panel.append(label,brief,generate,status,result,apply);scroll.append(panel);
  }
  async function loadAI(){
    clearAI();aiAvailable=false;var scope=aiScope(),button=document.getElementById('editor-ai-generate');button.disabled=true;
    if(!document.getElementById('builder-flow-campaign').value){aiStatus('Selecione uma campanha primeiro.');return;}
    aiStatus('Consultando disponibilidade…');
    try{var data=await window.apiFetch(aiUrl());if(scope!==aiScope())return;if(data.status!=='success')throw new Error(data.message||'Não foi possível consultar a IA.');aiAvailable=!!(data.configured&&data.quota&&data.quota.daily_remaining>0&&data.quota.total_remaining>0);aiStatus(!data.configured?'Configure ANTHROPIC_API_KEY no EasyPanel e faça o deploy.':aiAvailable?'Tentativas restantes hoje: '+data.quota.daily_remaining+'.':'Limite de gerações atingido.');}catch(e){if(scope===aiScope())aiStatus(e.message);}finally{button.disabled=aiBusy||!aiAvailable;}
  }
  async function generateAI(){
    if(aiBusy||!aiAvailable)return;var brief=document.getElementById('editor-ai-brief').value.trim();if(!brief){aiStatus('Descreva o que deseja melhorar.');return;}
    var context={},scope=aiScope(),button=document.getElementById('editor-ai-generate');Object.keys(copyFields).forEach(function(k){context[k]=document.getElementById('inline-offer-'+k).value;});['price','quota_count'].forEach(function(k){context[k]=Number(document.getElementById('inline-offer-'+k).value);});
    aiBusy=true;button.disabled=true;clearAI();aiStatus('Gerando sugestão…');
    try{var data=await window.apiFetch(aiUrl(),{method:'POST',body:JSON.stringify({brief:brief,context:context})});if(scope!==aiScope())return;if(data.quota)aiAvailable=data.quota.daily_remaining>0&&data.quota.total_remaining>0;if(data.status!=='success')throw new Error(data.message||'Não foi possível gerar.');if(!data.suggestion||!Object.keys(copyFields).every(function(k){return typeof data.suggestion[k]==='string';}))throw new Error('Sugestão inválida.');aiSuggestion={scope:scope,values:data.suggestion};var list=document.getElementById('editor-ai-result');Object.keys(copyFields).forEach(function(k){list.append(el('dt',null,copyFields[k]),el('dd',null,data.suggestion[k]));});document.getElementById('editor-ai-apply').hidden=false;aiStatus('Revise os textos e clique em Aplicar à oferta. Depois salve o rascunho.');}catch(e){if(scope===aiScope())aiStatus(e.message);}finally{aiBusy=false;button.disabled=!aiAvailable;}
  }
  function focusField(key){initialize();var tab=Object.keys(groups).find(function(k){return groups[k].includes(key);});if(!tab)return;
    activeTab=tab;render();var input=document.getElementById('inline-offer-'+key);if(input){var row=input.closest('.editor-field-row');row.hidden=false;input.focus();input.scrollIntoView({block:'nearest'});}
  }
  window.organizarPainelEditor=update;window.selecionarPainelEditor=selectTab;window.focarCampoOferta=focusField;
  document.addEventListener('DOMContentLoaded',initialize);
})(window,document);
