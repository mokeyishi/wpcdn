(()=>{"use strict";const e=window.wp.element,t=window.wp.components,o=window.wp.editPost,a=window.wp.compose,n=window.wp.data,i=window.wp.plugins,s=window.wp.i18n,l=(0,a.compose)([(0,n.withSelect)((e=>{const{getEditedPostAttribute:t}=e("core/editor");return{meta:t("meta")}})),(0,n.withDispatch)(((e,t)=>{let{meta:o}=t;const{editPost:a}=e("core/editor");return{updateMeta(e){a({meta:{...o,...e}})}}}))])((a=>{let{meta:n,updateMeta:i}=a;const l=n.lazy_load_responsive_images_disabled;return(0,e.createElement)(o.PluginPostStatusInfo,{className:"lazy-loader-plugin"},(0,e.createElement)("div",null,(0,e.createElement)(t.CheckboxControl,{label:(0,s.__)("Disable Lazy Loader","lazy-loading-responsive-images"),checked:l,onChange:e=>{i({lazy_load_responsive_images_disabled:e||0})}})))}));(0,i.registerPlugin)("lazy-loader-gutenberg",{render:l})})();