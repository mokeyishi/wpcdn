(()=>{"use strict";const e=window.React,t=window.wp.components,r=window.wp.i18n,n=window.wp.element;function a(){return(0,e.createElement)("div",{className:"generateblocks-dashboard"},(0,e.createElement)("div",{className:"gblocks-dashboard-intro-content"},(0,e.createElement)("h2",null,(0,r.__)("GenerateBlocks","generateblocks")),(0,e.createElement)("p",null,(0,r.__)("Take WordPress to the next level.","generateblocks")),(0,e.createElement)("div",{className:"gblocks-sub-navigation"},!generateblocksDashboard.gbpVersion&&(0,e.createElement)(t.Button,{variant:"primary",href:"https://generatepress.com/blocks/",target:"_blank",rel:"noreferrer noopener"},(0,r.__)("GenerateBlocks Pro","generateblocks")),(0,e.createElement)(t.Button,{variant:"secondary",href:"https://generatepress.com/blocks",target:"_blank",rel:"noreferrer noopener"},(0,r.__)("Learn more","generateblocks")),(0,e.createElement)(t.Button,{variant:"secondary",href:"https://docs.generateblocks.com",target:"_blank",rel:"noreferrer noopener"},(0,r.__)("Documentation","generateblocks")))),(0,e.createElement)(t.PanelBody,{title:(0,r.__)("Information","generateblocks"),className:"gb-dashboard-info"},(0,e.createElement)("div",{className:"gblocks-dashboard-panel-row-wrapper"},(0,e.createElement)(t.PanelRow,null,(0,e.createElement)("ul",{style:{marginBottom:0}},(0,e.createElement)("li",null,(0,e.createElement)("strong",null,"Version:")," ",generateblocksDashboard.gbVersion),generateblocksDashboard.gbpVersion?(0,e.createElement)("li",null,(0,e.createElement)("strong",null,"Pro Version:")," ",generateblocksDashboard.gbpVersion):(0,e.createElement)("li",null,(0,e.createElement)("strong",null,"Pro Version:")," Not Installed. ",(0,e.createElement)("a",{href:"https://generatepress.com/blocks"},"Get Pro")))))))}window.addEventListener("DOMContentLoaded",(()=>{var t,r;t=document.getElementById("gblocks-dashboard"),r=(0,e.createElement)(a,null),void 0!==n.createRoot?(0,n.createRoot)(t).render(r):(0,n.render)(r,t)}))})();