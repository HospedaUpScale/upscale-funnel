(function(window,document){
  'use strict';
  var pages=[], generation=0;
  function ws(){return window.currentWsId||1;}
  function node(tag,text){var el=document.createElement(tag);if(text!==undefined)el.textContent=text;return el;}
  function button(text,fn){var b=node('button',text);b.type='button';b.className='btn-secondary';b.style.cssText='font-size:12px;padding:6px 10px;min-height:30px;';b.onclick=fn;return b;}
  function openPage(page){window.location.href='index.html?workspace_id='+ws()+'&page_editor=1&funnel_id='+page.id+(page.campaign_id?'&campaign_id='+page.campaign_id:'')+'#sites';}
  async function action(page,kind){
    if(kind==='delete'&&!window.confirm('Excluir a página “'+page.name+'”? Esta ação não pode ser desfeita.'))return;
    if(kind==='unpublish'&&!window.confirm('Retirar esta página do ar? O link próprio ficará indisponível.'))return;
    try{
      var url='../api/admin/funnels.php?workspace_id='+ws()+(kind==='delete'?'&id='+page.id:'');
      var result=await window.apiFetch(url,{method:kind==='delete'?'DELETE':'POST',body:kind==='delete'?undefined:JSON.stringify({workspace_id:ws(),id:page.id,action:kind})});
      if(result.status!=='success')throw new Error(result.message||'Não foi possível concluir.');
      window.showToast(result.message||'Página atualizada.','success');
      if(document.getElementById('view-pages').classList.contains('active'))carregarGerenciadorPaginas();
      if(window.selectedCampaignId)carregarPaginasCampanha(window.selectedCampaignId);
    }catch(e){window.showToast(e.message,'error');}
  }
  function render(container,rows){
    container.replaceChildren();
    if(!rows.length){container.append(node('p','Nenhuma página encontrada. Crie uma página para começar.'));return;}
    var table=node('table');table.className='pages-management-table';
    var head=node('tr');['Página','Campanha','Status','Endereço próprio','Ações'].forEach(function(t){head.append(node('th',t));});var thead=node('thead');thead.append(head);table.append(thead);var body=node('tbody');
    rows.forEach(function(p){var row=node('tr'),name=node('td',p.name),campaign=node('td',p.campaign_name||'Sem campanha'),status=node('td',p.is_published?'Publicada':'Rascunho');
      if(p.is_primary){var badge=node('small',' · Principal');badge.style.color='#1764ff';name.append(badge);}
      var address=node('td'),actions=node('td'),group=node('div');group.style.cssText='display:flex;gap:6px;flex-wrap:wrap;';
      if(p.is_published){var link=node('a','/f/'+p.slug+' ↗');link.href=p.public_url;link.target='_blank';link.rel='noopener';address.append(link);}else address.textContent='/f/'+p.slug;
      group.append(button('Editar',function(){openPage(p);}),button('Duplicar',function(){action(p,'duplicate');}));
      if(p.is_published){group.append(button('Copiar link',function(){window.copiarValor(p.public_url);}));if(p.campaign_id&&!p.is_primary)group.append(button('Tornar principal',function(){action(p,'set_primary');}));group.append(button('Despublicar',function(){action(p,'unpublish');}));}
      group.append(button('Excluir',function(){action(p,'delete');}));actions.append(group);row.append(name,campaign,status,address,actions);body.append(row);
    });table.append(body);container.append(table);
  }
  async function carregarGerenciadorPaginas(){
    var current=++generation, workspace=ws(),status=document.getElementById('pages-manager-status');status.textContent='Carregando páginas…';
    try{var results=await Promise.all([window.apiFetch('../api/admin/funnels.php?summary=1&workspace_id='+workspace),window.apiFetch('../api/admin/campaigns.php?workspace_id='+workspace)]);
      if(current!==generation||workspace!==ws())return;
      if(results.some(function(r){return r.status!=='success';}))throw new Error('Não foi possível carregar as páginas.');
      pages=results[0].funnels||[];var select=document.getElementById('pages-campaign-filter'),previous=select.value;select.replaceChildren(new Option('Todas as campanhas',''),new Option('Sem campanha','none'));
      (results[1].campaigns||[]).forEach(function(c){select.add(new Option(c.title,String(c.id)));});select.value=previous;filtrarPaginasGerenciador();
    }catch(e){if(current===generation){status.textContent=e.message;document.getElementById('pages-manager-list').replaceChildren();}}
  }
  function filtrarPaginasGerenciador(){var query=document.getElementById('pages-search').value.toLowerCase(),campaign=document.getElementById('pages-campaign-filter').value,status=document.getElementById('pages-status-filter').value;
    var filtered=pages.filter(function(p){return (!query||(p.name+' '+p.slug).toLowerCase().includes(query))&&(!status||p.status===status)&&(!campaign||(campaign==='none'?!p.campaign_id:String(p.campaign_id)===campaign));});
    document.getElementById('pages-manager-status').textContent=filtered.length+' página(s) encontrada(s)';render(document.getElementById('pages-manager-list'),filtered);
  }
  async function carregarPaginasCampanha(id){var workspace=ws(),container=document.getElementById('campaign-pages-list');container.textContent='Carregando páginas…';
    try{var data=await window.apiFetch('../api/admin/funnels.php?summary=1&workspace_id='+workspace+'&campaign_id='+id);if(window.selectedCampaignId!==id||ws()!==workspace)return;if(data.status!=='success')throw new Error(data.message);render(container,data.funnels||[]);}catch(e){if(window.selectedCampaignId===id)container.textContent=e.message;}
  }
  function criarPaginaDaCampanha(id){if(!id)return;window.abrirTelaSelecaoModelos('campaign',id);}
  window.carregarGerenciadorPaginas=carregarGerenciadorPaginas;window.filtrarPaginasGerenciador=filtrarPaginasGerenciador;window.carregarPaginasCampanha=carregarPaginasCampanha;window.criarPaginaDaCampanha=criarPaginaDaCampanha;
})(window,document);
