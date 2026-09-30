import{r as p}from"./app-CMCebSGe.js";let C={data:""},D=t=>{if(typeof window=="object"){let e=(t?t.querySelector("#_goober"):window._goober)||Object.assign(document.createElement("style"),{innerHTML:" ",id:"_goober"});return e.nonce=window.__nonce__,e.parentNode||(t||document.head).appendChild(e),e.firstChild}return t||C},L=/(?:([\u0080-\uFFFF\w-%@]+) *:? *([^{;]+?);|([^;}{]*?) *{)|(}\s*)/g,S=/\/\*[^]*?\*\/|  +/g,A=/\n+/g,g=(t,e)=>{let a="",o="",s="";for(let i in t){let r=t[i];i[0]=="@"?i[1]=="i"?a=i+" "+r+";":o+=i[1]=="f"?g(r,i):i+"{"+g(r,i[1]=="k"?"":e)+"}":typeof r=="object"?o+=g(r,e?e.replace(/([^,])+/g,n=>i.replace(/([^,]*:\S+\([^)]*\))|([^,])+/g,l=>/&/.test(l)?l.replace(/&/g,n):n?n+" "+l:l)):i):r!=null&&(i=/^--/.test(i)?i:i.replace(/[A-Z]/g,"-$&").toLowerCase(),s+=g.p?g.p(i,r):i+":"+r+";")}return a+(e&&s?e+"{"+s+"}":s)+o},u={},z=t=>{if(typeof t=="object"){let e="";for(let a in t)e+=a+z(t[a]);return e}return t},P=(t,e,a,o,s)=>{let i=z(t),r=u[i]||(u[i]=(l=>{let d=0,m=11;for(;d<l.length;)m=101*m+l.charCodeAt(d++)>>>0;return"go"+m})(i));if(!u[r]){let l=i!==t?t:(d=>{let m,b,h=[{}];for(;m=L.exec(d.replace(S,""));)m[4]?h.shift():m[3]?(b=m[3].replace(A," ").trim(),h.unshift(h[0][b]=h[0][b]||{})):h[0][m[1]]=m[2].replace(A," ").trim();return h[0]})(t);u[r]=g(s?{["@keyframes "+r]:l}:l,a?"":"."+r)}let n=a&&u.g?u.g:null;return a&&(u.g=u[r]),((l,d,m,b)=>{b?d.data=d.data.replace(b,l):d.data.indexOf(l)===-1&&(d.data=m?l+d.data:d.data+l)})(u[r],e,o,n),r},T=(t,e,a)=>t.reduce((o,s,i)=>{let r=e[i];if(r&&r.call){let n=r(a),l=n&&n.props&&n.props.className||/^go/.test(n)&&n;r=l?"."+l:n&&typeof n=="object"?n.props?"":g(n,""):n===!1?"":n}return o+s+(r??"")},"");function w(t){let e=this||{},a=t.call?t(e.p):t;return P(a.unshift?a.raw?T(a,[].slice.call(arguments,1),e.p):a.reduce((o,s)=>Object.assign(o,s&&s.call?s(e.p):s),{}):a,D(e.target),e.g,e.o,e.k)}let O,$,E;w.bind({g:1});let f=w.bind({k:1});function H(t,e,a,o){g.p=e,O=t,$=a,E=o}function y(t,e){let a=this||{};return function(){let o=arguments;function s(i,r){let n=Object.assign({},i),l=n.className||s.className;a.p=Object.assign({theme:$&&$()},n),a.o=/ *go\d+/.test(l),n.className=w.apply(a,o)+(l?" "+l:"");let d=t;return t[0]&&(d=n.as||t,delete n.as),E&&d[0]&&E(n),O(d,n)}return s}}var M=t=>typeof t=="function",k=(t,e)=>M(t)?t(e):t,Z=(()=>{let t=0;return()=>(++t).toString()})(),q=(()=>{let t;return()=>{if(t===void 0&&typeof window<"u"){let e=matchMedia("(prefers-reduced-motion: reduce)");t=!e||e.matches}return t}})(),Q=20,_="default",I=(t,e)=>{let{toastLimit:a}=t.settings;switch(e.type){case 0:return{...t,toasts:[e.toast,...t.toasts].slice(0,a)};case 1:return{...t,toasts:t.toasts.map(r=>r.id===e.toast.id?{...r,...e.toast}:r)};case 2:let{toast:o}=e;return I(t,{type:t.toasts.find(r=>r.id===o.id)?1:0,toast:o});case 3:let{toastId:s}=e;return{...t,toasts:t.toasts.map(r=>r.id===s||s===void 0?{...r,dismissed:!0,visible:!1}:r)};case 4:return e.toastId===void 0?{...t,toasts:[]}:{...t,toasts:t.toasts.filter(r=>r.id!==e.toastId)};case 5:return{...t,pausedAt:e.time};case 6:let i=e.time-(t.pausedAt||0);return{...t,pausedAt:void 0,toasts:t.toasts.map(r=>({...r,pauseDuration:r.pauseDuration+i}))}}},R=[],W={toasts:[],pausedAt:void 0,settings:{toastLimit:Q}},x={},N=(t,e=_)=>{x[e]=I(x[e]||W,t),R.forEach(([a,o])=>{a===e&&o(x[e])})},F=t=>Object.keys(x).forEach(e=>N(t,e)),Y=t=>Object.keys(x).find(e=>x[e].toasts.some(a=>a.id===t)),j=(t=_)=>e=>{N(e,t)},B=(t,e="blank",a)=>({createdAt:Date.now(),visible:!0,dismissed:!1,type:e,ariaProps:{role:"status","aria-live":"polite"},message:t,pauseDuration:0,...a,id:a?.id||Z()}),v=t=>(e,a)=>{let o=B(e,t,a);return j(o.toasterId||Y(o.id))({type:2,toast:o}),o.id},c=(t,e)=>v("blank")(t,e);c.error=v("error");c.success=v("success");c.loading=v("loading");c.custom=v("custom");c.dismiss=(t,e)=>{let a={type:3,toastId:t};e?j(e)(a):F(a)};c.dismissAll=t=>c.dismiss(void 0,t);c.remove=(t,e)=>{let a={type:4,toastId:t};e?j(e)(a):F(a)};c.removeAll=t=>c.remove(void 0,t);c.promise=(t,e,a)=>{let o=c.loading(e.loading,{...a,...a?.loading});return typeof t=="function"&&(t=t()),t.then(s=>{let i=e.success?k(e.success,s):void 0;return i?c.success(i,{id:o,...a,...a?.success}):c.dismiss(o),s}).catch(s=>{let i=e.error?k(e.error,s):void 0;i?c.error(i,{id:o,...a,...a?.error}):c.dismiss(o)}),t};var G=f`
from {
  transform: scale(0) rotate(45deg);
	opacity: 0;
}
to {
 transform: scale(1) rotate(45deg);
  opacity: 1;
}`,J=f`
from {
  transform: scale(0);
  opacity: 0;
}
to {
  transform: scale(1);
  opacity: 1;
}`,K=f`
from {
  transform: scale(0) rotate(90deg);
	opacity: 0;
}
to {
  transform: scale(1) rotate(90deg);
	opacity: 1;
}`,U=y("div")`
  width: 20px;
  opacity: 0;
  height: 20px;
  border-radius: 10px;
  background: ${t=>t.primary||"#ff4b4b"};
  position: relative;
  transform: rotate(45deg);

  animation: ${G} 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)
    forwards;
  animation-delay: 100ms;

  &:after,
  &:before {
    content: '';
    animation: ${J} 0.15s ease-out forwards;
    animation-delay: 150ms;
    position: absolute;
    border-radius: 3px;
    opacity: 0;
    background: ${t=>t.secondary||"#fff"};
    bottom: 9px;
    left: 4px;
    height: 2px;
    width: 12px;
  }

  &:before {
    animation: ${K} 0.15s ease-out forwards;
    animation-delay: 180ms;
    transform: rotate(90deg);
  }
`,V=f`
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
`,X=y("div")`
  width: 12px;
  height: 12px;
  box-sizing: border-box;
  border: 2px solid;
  border-radius: 100%;
  border-color: ${t=>t.secondary||"#e0e0e0"};
  border-right-color: ${t=>t.primary||"#616161"};
  animation: ${V} 1s linear infinite;
`,tt=f`
from {
  transform: scale(0) rotate(45deg);
	opacity: 0;
}
to {
  transform: scale(1) rotate(45deg);
	opacity: 1;
}`,et=f`
0% {
	height: 0;
	width: 0;
	opacity: 0;
}
40% {
  height: 0;
	width: 6px;
	opacity: 1;
}
100% {
  opacity: 1;
  height: 10px;
}`,at=y("div")`
  width: 20px;
  opacity: 0;
  height: 20px;
  border-radius: 10px;
  background: ${t=>t.primary||"#61d345"};
  position: relative;
  transform: rotate(45deg);

  animation: ${tt} 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)
    forwards;
  animation-delay: 100ms;
  &:after {
    content: '';
    box-sizing: border-box;
    animation: ${et} 0.2s ease-out forwards;
    opacity: 0;
    animation-delay: 200ms;
    position: absolute;
    border-right: 2px solid;
    border-bottom: 2px solid;
    border-color: ${t=>t.secondary||"#fff"};
    bottom: 6px;
    left: 6px;
    height: 10px;
    width: 6px;
  }
`,rt=y("div")`
  position: absolute;
`,ot=y("div")`
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-width: 20px;
  min-height: 20px;
`,it=f`
from {
  transform: scale(0.6);
  opacity: 0.4;
}
to {
  transform: scale(1);
  opacity: 1;
}`,st=y("div")`
  position: relative;
  transform: scale(0.6);
  opacity: 0.4;
  min-width: 20px;
  animation: ${it} 0.3s 0.12s cubic-bezier(0.175, 0.885, 0.32, 1.275)
    forwards;
`,nt=({toast:t})=>{let{icon:e,type:a,iconTheme:o}=t;return e!==void 0?typeof e=="string"?p.createElement(st,null,e):e:a==="blank"?null:p.createElement(ot,null,p.createElement(X,{...o}),a!=="loading"&&p.createElement(rt,null,a==="error"?p.createElement(U,{...o}):p.createElement(at,{...o})))},lt=t=>`
0% {transform: translate3d(0,${t*-200}%,0) scale(.6); opacity:.5;}
100% {transform: translate3d(0,0,0) scale(1); opacity:1;}
`,ct=t=>`
0% {transform: translate3d(0,0,-1px) scale(1); opacity:1;}
100% {transform: translate3d(0,${t*-150}%,-1px) scale(.6); opacity:0;}
`,dt="0%{opacity:0;} 100%{opacity:1;}",pt="0%{opacity:1;} 100%{opacity:0;}",mt=y("div")`
  display: flex;
  align-items: center;
  background: #fff;
  color: #363636;
  line-height: 1.3;
  will-change: transform;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1), 0 3px 3px rgba(0, 0, 0, 0.05);
  max-width: 350px;
  pointer-events: auto;
  padding: 8px 10px;
  border-radius: 8px;
`,ut=y("div")`
  display: flex;
  justify-content: center;
  margin: 4px 10px;
  color: inherit;
  flex: 1 1 auto;
  white-space: pre-line;
`,ft=(t,e)=>{let a=t.includes("top")?1:-1,[o,s]=q()?[dt,pt]:[lt(a),ct(a)];return{animation:e?`${f(o)} 0.35s cubic-bezier(.21,1.02,.73,1) forwards`:`${f(s)} 0.4s forwards cubic-bezier(.06,.71,.55,1)`}};p.memo(({toast:t,position:e,style:a,children:o})=>{let s=t.height?ft(t.position||e||"top-center",t.visible):{opacity:0},i=p.createElement(nt,{toast:t}),r=p.createElement(ut,{...t.ariaProps},k(t.message,t));return p.createElement(mt,{className:t.className,style:{...s,...a,...t.style}},typeof o=="function"?o({icon:i,message:r}):p.createElement(p.Fragment,null,i,r))});H(p.createElement);w`
  z-index: 9999;
  > * {
    pointer-events: auto;
  }
`;export{c as n};
