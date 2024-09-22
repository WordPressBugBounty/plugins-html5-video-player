!function(){"use strict";var e={n:function(t){var a=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(a,{a:a}),a},d:function(t,a){for(var r in a)e.o(a,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:a[r]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=React,a=e.n(t),r=ReactDOM;var o=e=>{let t=null;try{t=JSON.parse(e)}catch(e){console.warn(e.message)}return t};var l=function(e){return/^(?:(?:https?:\/\/)?(?:www\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=)?([a-zA-Z0-9_-]+))$/.test(e)?e:!!/^[a-zA-Z0-9_-]{11}$/.test(e)&&`https://www.youtube.com/watch?v=${e}`};var n=function(e){if(!e)return!1;const t=/^(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/(video\/)?(\d+)(\/[^\s]*)?$/,a=e?.match(t)?.[5];return a?`https://player.vimeo.com/video/${a}`:isNaN(e)?t.test(e):`https://player.vimeo.com/video/${e}`};function s(){return s=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var a=arguments[t];for(var r in a)Object.prototype.hasOwnProperty.call(a,r)&&(e[r]=a[r])}return e},s.apply(this,arguments)}var i=e=>{let{captions:t,poster:r,source:o,qualities:l,isPremium:n,className:i="",reference:c,...d}=e;return a().createElement(a().Fragment,null,a().createElement("video",s({crossOrigin:!0,className:i,id:"player","data-poster":r||"",ref:c,src:o},d,{style:{width:"100%",maxWidth:"100%"}}),n&&a().createElement(a().Fragment,null,Array.isArray(t)&&t.map(((e,t)=>{if(!e.caption_file)return;const r=e.label.split("/");return a().createElement("track",{key:t,kind:"captions",srcLang:r[1]||" ",label:r[0]||"no label",src:e.caption_file})})),!["m3u8","mpd"].includes(o?.split(".").pop())&&a().createElement(a().Fragment,null,a().createElement("source",{src:o,size:720,type:`video/${o?.split(".").pop()}`}),Array.isArray(l)&&l.map(((e,t)=>{}))))),d["data-poster"]&&a().createElement("div",{className:"preload_poster",style:{background:`url(${d["data-poster"]})`}}))};function c(){return c=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var a=arguments[t];for(var r in a)Object.prototype.hasOwnProperty.call(a,r)&&(e[r]=a[r])}return e},c.apply(this,arguments)}var d=e=>{let{source:t="https://www.youtube.com/watch?v=MLpWrANjFbI",className:a="",...r}=e;return React.createElement("div",c({className:`plyr__video-embed ${a}`,id:"player"},r),React.createElement("iframe",{src:`${t}?origin=${window.location.origin}&iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1`,allowfullscreen:!0,allowtransparency:!0,allow:"autoplay"}),r["data-poster"]&&React.createElement("div",{className:"preload_poster",style:{background:`url(${r["data-poster"]})`}}))};function u(){return u=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var a=arguments[t];for(var r in a)Object.prototype.hasOwnProperty.call(a,r)&&(e[r]=a[r])}return e},u.apply(this,arguments)}var m=e=>{let{source:t="https://player.vimeo.com/video/76979871",className:r="",...o}=e;return a().createElement("div",u({className:`plyr__video-embed ${r}`,id:"player"},o),a().createElement("iframe",{src:`${t}?loop=false&amp;byline=false&amp;portrait=false&amp;title=false&amp;speed=true&amp;transparent=0&amp;gesture=media`,allowfullscreen:!0,allowtransparency:!0,allow:"autoplay"}),o["data-poster"]&&a().createElement("div",{className:"preload_poster",style:{background:`url(${o["data-poster"]})`}}))};var p=e=>{let{video:a,options:r,player:o,setPlayer:s}=e;const{video_source:c,h5vp_video_source:u,video_thumb:p,video_title:v,h5vp_video_provider:y}=a,[_,f]=(0,t.useState)(null),[b,h]=(0,t.useState)(!1),E=(0,t.useRef)();return(0,t.useEffect)((()=>{if(o){const e={src:"library"===y?c:u};"library"===y?e.type="video/mp4":e.provider=y,o.source={type:"video",sources:[e]},b&&o?.on("ready",(()=>{o.play()}))}else{document.querySelectorAll("#player_library, #player_youtube, #player_vimeo").forEach((e=>{e.classList.add("hidden")}));const e=E.current?.querySelector(`#player_${y}`);if(e){e.classList.remove("hidden");const t=new Plyr(e,r);s(t),h(!0)}}console.log({shouldPlay:b})}),[a]),(0,t.useEffect)((()=>{o?.on("ready",(()=>{E.current?.parentNode?.parentNode?.classList.add("playlist_loaded")}))}),[o]),React.createElement("div",{className:"video__main",ref:E},React.createElement("div",{className:"video__thumb video__thumb--big mb-1"},React.createElement(i,{style:{aspectRatio:"16/9"},id:"player_library","data-poster":p,qualities:[],captions:[],source:c,src:c,isPremium:!0}),React.createElement(d,{id:"player_youtube",source:l(u),"data-poster":p}),React.createElement(m,{id:"player_vimeo",source:n(u),"data-poster":p})))};var v=function(e){return e.replace(/([a-z])([A-Z])/g,"$1-$2").toLowerCase()};var y=e=>{let{styles:a={},uniqueId:r}=e;const[o,l]=(0,t.useState)(null);return(0,t.useEffect)((()=>{let e="";"object"==typeof a&&Object.keys(a).map((t=>{if("object"==typeof a[t]){let o="";Object.keys(a[t]).map((e=>{o+=`${v(e)}: ${a[t][e]}`})),e+=`#${r} ${[".","#"].includes(t[0])?"":"."}${t}{${o}} `}})),l(e)}),[a,r]),React.createElement("style",{dangerouslySetInnerHTML:{__html:o}})};var _=e=>{let{attributes:r,nonce:o}=e;const{videos:l,styles:n,options:s,uniqueId:i}=r,[c,d]=(0,t.useState)(l?.[0]||{}),[u,m]=(0,t.useState)(0),[v,_]=(0,t.useState)(null),f=(0,t.useRef)();return(0,t.useEffect)((()=>{setTimeout((()=>{f.current&&(f.current.querySelector(".video__right").style.height=f.current.querySelector(".video__left .video__main").offsetHeight+"px")}),100)}),[c]),(0,t.useEffect)((()=>{v&&s.autoplayNextVideo&&v.on("ended",(()=>{const e=document.querySelector(".item-active"),t=e.nextElementSibling?.dataset?.index||0;t&&m(t)}))}),[v]),(0,t.useEffect)((()=>{window.currentIndex=u,d(l[u]||l[0]),document.querySelectorAll(".item-active").forEach((e=>{e.classList.remove("item-active")})),document.querySelector(`.video-item[data-index="${u}"]`)?.classList.add("item-active")}),[u]),console.log(r),a().createElement("div",{id:i,className:"video video--bg",ref:f},a().createElement(y,{styles:n,uniqueId:i}),a().createElement("div",{className:"h5vp_playlist_container"},a().createElement("div",{className:"video__left video__wrapper"},a().createElement(p,{video:c,options:s,player:v,setPlayer:_})),a().createElement("div",{className:"video__right video__wrapper",style:{aspectRatio:"4/4.3",overflow:"hidden"}},l.map(((e,t)=>{const{video_thumb:r,video_source:o,video_title:l,video_desc:n,h5vp_video_provider:s}=e;return a().createElement("div",{key:t,className:`video-item video-item-${t} ${u===t?"item-active":""}`,onClick:()=>{d(e),m(t)},"data-index":t},a().createElement("div",{className:"video-block"},a().createElement("div",{className:"video-block__container video__container"},a().createElement("div",{className:"video__thumb video__thumb--small"},a().createElement("img",{src:r,alt:"video"}))),a().createElement("div",{className:"video-block__content"},a().createElement("h2",{title:l,className:"video-block__title video-block__title--small"},l),a().createElement("p",{className:"video-block__description"},n," "))))})))))};document.addEventListener("DOMContentLoaded",(function(){b()}));const f=e=>(console.log(e),a().createElement(_,e)),b=()=>{const e=document.querySelectorAll(".h5vp_playlist");e?.forEach((e=>{const t=o(e.dataset.attributes);if(e.removeAttribute("data-attributes"),e.removeAttribute("data-data"),!t)return!1;const l=e.dataset.nonce;if("object"==typeof t&&e){(0,r.createRoot)(e).render(a().createElement(f,{attributes:t,nonce:l}))}}))}}();
//# sourceMappingURL=frontend-playlist.js.map