(function(window,document){
  'use strict';
  var states={};
  window.paginarLista=function(rows,host,refresh){
    var key=rows.map(function(row){return row.id;}).join(','),state=states[host.id];
    if(!state||state.key!==key)state=states[host.id]={key:key,page:1,size:10};
    var total=Math.max(1,Math.ceil(rows.length/state.size));state.page=Math.min(state.page,total);host.replaceChildren();
    host.setAttribute('aria-label','Paginação');host.style.cssText='display:flex;gap:12px;align-items:center;justify-content:flex-end;flex-wrap:wrap;padding:14px 0;';
    function button(text,disabled,action){var b=document.createElement('button');b.type='button';b.className='btn-secondary';b.textContent=text;b.disabled=disabled;b.onclick=action;host.append(b);}
    var label=document.createElement('span');label.textContent=rows.length?'Página '+state.page+' de '+total+' · '+rows.length+' registros':'0 registros';label.setAttribute('role','status');
    button('Anterior',state.page===1,function(){state.page--;refresh();});host.append(label);button('Próxima',state.page===total,function(){state.page++;refresh();});
    return rows.slice((state.page-1)*state.size,state.page*state.size);
  };
  window.paginadorDepois=function(container){var id=container.id+'-pagination',host=document.getElementById(id);if(!host){host=document.createElement('nav');host.id=id;container.after(host);}return host;};
})(window,document);
