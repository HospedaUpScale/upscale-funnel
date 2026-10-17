(function(window,document){
  'use strict';
  var ready=false,last={},activeTab='content';
  var groups={
    content:['headline_start','headline_highlight','subtitle_text','wheel_heading','wheel_highlight','wheel_subtitle','wheel_spin_label','title','subtitle','price','quota_count','offer_headline_start','offer_headline_highlight','cta_label','cta_subtext','decline_label','timer_minutes'],
    media:['vturb_embed','video_src'],
    appearance:['page_bg','primary_color','accent_color','card_bg','card_bg_alt','title_from','title_to','text_color','text_secondary','cta_bg','cta_text','badge_bg','badge_text','border_color','foil_start','foil_end','sound_enabled','sound_volume'],
    payment:['checkout_mode','legacy_checkout_url']
  };
  var titles={page:'Blocos da página',content:'Conteúdo',media:'Mídia',appearance:'Aparência',payment:'Pagamento'};
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
    scroll.append(offer);var notice=el('p','editor-shared-notice','Os dados da oferta são compartilhados pelas páginas desta campanha.');scroll.append(notice);
    var footer=el('div','editor-panel-footer'),save=el('button','btn-primary','Salvar rascunho'),pages=el('button','btn-secondary','Páginas');save.type=pages.type='button';save.id='editor-save-draft';save.onclick=async function(){save.disabled=true;try{await window.salvarFunilRascunho();}finally{save.disabled=false;}};pages.onclick=function(){window.togglePagesDrawer(true);};footer.append(save,pages);
    sidebar.replaceChildren(header,tabs,scroll,pageTools,footer);ready=true;render();
  }
  function selectTab(key){initialize();activeTab=key;render();}
  function render(){
    if(!ready)return;
    var native=last.target==='experience',shell=document.querySelector('.elementor-shell');if(shell)shell.classList.toggle('editor-native-mode',native);
    if(native&&activeTab==='page')activeTab='content';
    document.getElementById('editor-panel-tab-page').hidden=native;
    var page=activeTab==='page';document.getElementById('editor-page-tools').hidden=!page;document.getElementById('editor-form-scroll').hidden=page;
    var screen=last.stage==='scratch'?last.mechanic:'offer';
    var heading=page?'Blocos da página':activeTab==='content'?(screen==='wheel'?'Conteúdo da roleta':screen==='scratch'?'Conteúdo da raspadinha':'Conteúdo da oferta'):titles[activeTab];
    document.getElementById('editor-panel-heading').textContent=activeTab==='content'&&last.offerRole==='downsell'?'Conteúdo do downsell':heading;
    document.querySelector('.editor-shared-notice').textContent=last.offerRole==='downsell'?'Oferta alternativa desta campanha. Edite o preço e publique para disponibilizar o link de recusa.':'Os dados da oferta principal são compartilhados pelas páginas desta campanha.';
    Object.keys(titles).forEach(function(key){document.getElementById('editor-panel-tab-'+key).setAttribute('aria-selected',String(activeTab===key));});
    Object.keys(groups).forEach(function(key){var panel=document.getElementById('editor-field-panel-'+key);panel.hidden=activeTab!==key;panel.querySelectorAll('.editor-field-row').forEach(function(row){row.hidden=(key==='content'&&row.dataset.screen!==screen)||(row.dataset.field==='decline_label'&&last.offerRole==='downsell');});});
    document.querySelectorAll('[data-color-field]').forEach(function(picker){var value=document.getElementById('inline-offer-'+picker.dataset.colorField).value;if(/^#[0-9a-f]{6}$/i.test(value))picker.value=value;});
    document.getElementById('builder-inline-offer').open=true;
    // A link is relevant only for an external checkout.
    var external=document.getElementById('inline-offer-checkout_mode').value==='external_redirect';
    document.querySelector('[data-field="legacy_checkout_url"]').hidden=!external;
  }
  function update(state){
    initialize();var changed=state.target!==last.target||state.stage!==last.stage||state.mechanic!==last.mechanic;
    var editing=!!document.activeElement.closest('#builder-offer-fields');
    if(changed&&!editing)activeTab=state.target==='page'?'page':state.stage==='checkout'?'payment':'content';
    last=state;render();
  }
  function focusField(key){initialize();var tab=Object.keys(groups).find(function(k){return groups[k].includes(key);});if(!tab)return;
    activeTab=tab;render();var input=document.getElementById('inline-offer-'+key);if(input){var row=input.closest('.editor-field-row');row.hidden=false;input.focus();input.scrollIntoView({block:'nearest'});}
  }
  window.organizarPainelEditor=update;window.selecionarPainelEditor=selectTab;window.focarCampoOferta=focusField;
  document.addEventListener('DOMContentLoaded',initialize);
})(window,document);
