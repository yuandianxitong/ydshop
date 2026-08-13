import{q as O,ay as W,cp as Ce,aA as F,bi as X,bj as v,cq as we,bv as g,d as z,ad as f,bu as G,z as Pe,a1 as Ne,bn as H,bo as K,bp as J,f as w,j as $,Q as L,W as Q,F as q,aM as Se,p as j,N as ze,cr as ke,ch as Y,cs as Ae,ct as Re,cu as Le,bY as Z,x as ee,cv as je,cw as Oe,br as Ee,bs as Be,bA as Ie,bF as $e,bt as E,bm as _e,bR as Me,bC as Te,bq as Fe,bD as He,bW as Ke,cx as De,cy as We,cz as qe,cA as Ve,bO as Ue,o as Xe,bl as x,bk as A,cB as Ge,b as U,cC as Je,cD as Qe,ck as Ye,b_ as Ze,cE as et,cF as tt}from"./CmYNeFwQ.js";import{B as $t,cG as _t,cH as Mt,cd as Tt,cI as Ft,bw as Ht,cJ as Kt,cK as Dt,cL as Wt,cM as qt,cN as Vt,cO as Ut,cP as Xt,aK as Gt}from"./CmYNeFwQ.js";import{u as nt}from"./D-CfbOvs.js";import{c as Qt,N as Yt,a as Zt,d as en,b as tn,e as nn,f as on,g as rn,h as an,p as sn,s as ln,t as cn}from"./C305lqIq.js";import{N as fn,d as un,e as vn,c as hn}from"./BvyR7OmS.js";import{N as gn,i as mn}from"./Cl9oUe8z.js";import{b as xn,N as yn,a as Cn,r as wn,c as Pn,s as Nn}from"./DU9VjDW9.js";import{a as zn,T as kn,N as An,b as Rn,r as Ln,t as jn,c as On,d as En}from"./Bf4COJL9.js";import{N as In,s as $n}from"./14b7BRR0.js";import"./C9oi5PSW.js";function ot(){const t=O(Ce,null);return t===null&&W("use-dialog","No outer <n-dialog-provider /> founded."),t}const te=F("n-loading-bar"),ne=F("n-loading-bar-api");function it(t){const{primaryColor:e,errorColor:n}=t;return{colorError:n,colorLoading:e,height:"2px"}}const rt={common:X,self:it},at=v("loading-bar-container",`
 z-index: 5999;
 position: fixed;
 top: 0;
 left: 0;
 right: 0;
 height: 2px;
`,[we({enterDuration:"0.3s",leaveDuration:"0.8s"}),v("loading-bar",`
 width: 100%;
 transition:
 max-width 4s linear,
 background .2s linear;
 height: var(--n-height);
 `,[g("starting",`
 background: var(--n-color-loading);
 `),g("finishing",`
 background: var(--n-color-loading);
 transition:
 max-width .2s linear,
 background .2s linear;
 `),g("error",`
 background: var(--n-color-error);
 transition:
 max-width .2s linear,
 background .2s linear;
 `)])]);var _=function(t,e,n,o){function s(i){return i instanceof n?i:new n(function(r){r(i)})}return new(n||(n=Promise))(function(i,r){function c(d){try{u(o.next(d))}catch(h){r(h)}}function l(d){try{u(o.throw(d))}catch(h){r(h)}}function u(d){d.done?i(d.value):s(d.value).then(c,l)}u((o=o.apply(t,e||[])).next())})};function M(t,e){return`${e}-loading-bar ${e}-loading-bar--${t}`}const st=z({name:"LoadingBar",props:{containerClass:String,containerStyle:[String,Object]},setup(){const{inlineThemeDisabled:t}=H(),{props:e,mergedClsPrefixRef:n}=O(te),o=w(null),s=w(!1),i=w(!1),r=w(!1),c=w(!1);let l=!1;const u=w(!1),d=$(()=>{const{loadingBarStyle:p}=e;return p?p[u.value?"error":"loading"]:""});function h(){return _(this,void 0,void 0,function*(){s.value=!1,r.value=!1,l=!1,u.value=!1,c.value=!0,yield L(),c.value=!1})}function y(){return _(this,arguments,void 0,function*(p=0,B=80,I="starting"){if(i.value=!0,yield h(),l)return;r.value=!0,yield L();const S=o.value;S&&(S.style.maxWidth=`${p}%`,S.style.transition="none",S.offsetWidth,S.className=M(I,n.value),S.style.transition="",S.style.maxWidth=`${B}%`)})}function b(){return _(this,void 0,void 0,function*(){if(l||u.value)return;i.value&&(yield L()),l=!0;const p=o.value;p&&(p.className=M("finishing",n.value),p.style.maxWidth="100%",p.offsetWidth,r.value=!1)})}function a(){if(!(l||u.value))if(!r.value)y(100,100,"error").then(()=>{u.value=!0;const p=o.value;p&&(p.className=M("error",n.value),p.offsetWidth,r.value=!1)});else{u.value=!0;const p=o.value;if(!p)return;p.className=M("error",n.value),p.style.maxWidth="100%",p.offsetWidth,r.value=!1}}function m(){s.value=!0}function C(){s.value=!1}function N(){return _(this,void 0,void 0,function*(){yield h()})}const k=K("LoadingBar","-loading-bar",at,rt,e,n),R=$(()=>{const{self:{height:p,colorError:B,colorLoading:I}}=k.value;return{"--n-height":p,"--n-color-loading":I,"--n-color-error":B}}),P=t?J("loading-bar",void 0,R,e):void 0;return{mergedClsPrefix:n,loadingBarRef:o,started:i,loading:r,entering:s,transitionDisabled:c,start:y,error:a,finish:b,handleEnter:m,handleAfterEnter:C,handleAfterLeave:N,mergedLoadingBarStyle:d,cssVars:t?void 0:R,themeClass:P?.themeClass,onRender:P?.onRender}},render(){if(!this.started)return null;const{mergedClsPrefix:t}=this;return f(G,{name:"fade-in-transition",appear:!0,onEnter:this.handleEnter,onAfterEnter:this.handleAfterEnter,onAfterLeave:this.handleAfterLeave,css:!this.transitionDisabled},{default:()=>{var e;return(e=this.onRender)===null||e===void 0||e.call(this),Pe(f("div",{class:[`${t}-loading-bar-container`,this.themeClass,this.containerClass],style:this.containerStyle},f("div",{ref:"loadingBarRef",class:[`${t}-loading-bar`],style:[this.cssVars,this.mergedLoadingBarStyle]})),[[Ne,this.loading||!this.loading&&this.entering]])}})}}),lt=Object.assign(Object.assign({},K.props),{to:{type:[String,Object,Boolean],default:void 0},containerClass:String,containerStyle:[String,Object],loadingBarStyle:{type:Object}}),ct=z({name:"LoadingBarProvider",props:lt,setup(t){const e=Se(),n=w(null),o={start(){var i;e.value?(i=n.value)===null||i===void 0||i.start():L(()=>{var r;(r=n.value)===null||r===void 0||r.start()})},error(){var i;e.value?(i=n.value)===null||i===void 0||i.error():L(()=>{var r;(r=n.value)===null||r===void 0||r.error()})},finish(){var i;e.value?(i=n.value)===null||i===void 0||i.finish():L(()=>{var r;(r=n.value)===null||r===void 0||r.finish()})}},{mergedClsPrefixRef:s}=H(t);return j(ne,o),j(te,{props:t,mergedClsPrefixRef:s}),Object.assign(o,{loadingBarRef:n})},render(){var t,e;return f(q,null,f(Q,{disabled:this.to===!1,to:this.to||"body"},f(st,{ref:"loadingBarRef",containerStyle:this.containerStyle,containerClass:this.containerClass})),(e=(t=this.$slots).default)===null||e===void 0?void 0:e.call(t))}});function dt(){const t=O(ne,null);return t===null&&W("use-loading-bar","No outer <n-loading-bar-provider /> founded."),t}const ft=z({name:"ModalEnvironment",props:Object.assign(Object.assign({},ke),{internalKey:{type:String,required:!0},onInternalAfterLeave:{type:Function,required:!0}}),setup(t){const e=w(!0);function n(){const{onInternalAfterLeave:d,internalKey:h,onAfterLeave:y}=t;d&&d(h),y&&y()}function o(){const{onPositiveClick:d}=t;d?Promise.resolve(d()).then(h=>{h!==!1&&l()}):l()}function s(){const{onNegativeClick:d}=t;d?Promise.resolve(d()).then(h=>{h!==!1&&l()}):l()}function i(){const{onClose:d}=t;d?Promise.resolve(d()).then(h=>{h!==!1&&l()}):l()}function r(d){const{onMaskClick:h,maskClosable:y}=t;h&&(h(d),y&&l())}function c(){const{onEsc:d}=t;d&&d()}function l(){e.value=!1}function u(d){e.value=d}return{show:e,hide:l,handleUpdateShow:u,handleAfterLeave:n,handleCloseClick:i,handleNegativeClick:s,handlePositiveClick:o,handleMaskClick:r,handleEsc:c}},render(){const{handleUpdateShow:t,handleAfterLeave:e,handleMaskClick:n,handleEsc:o,show:s}=this;return f(ze,Object.assign({},this.$props,{show:s,onUpdateShow:t,onMaskClick:n,onEsc:o,onAfterLeave:e,internalAppear:!0,internalModal:!0}),this.$slots)}}),ut={to:[String,Object]},vt=z({name:"ModalProvider",props:ut,setup(){const t=w([]),e={};function n(r={}){const c=Z(),l=ee(Object.assign(Object.assign({},r),{key:c,destroy:()=>{var u;(u=e[`n-modal-${c}`])===null||u===void 0||u.hide()}}));return t.value.push(l),l}function o(r){const{value:c}=t;c.splice(c.findIndex(l=>l.key===r),1)}function s(){Object.values(e).forEach(r=>{r?.hide()})}const i={create:n,destroyAll:s};return j(je,i),j(Le,{clickedRef:Re(64),clickedPositionRef:Ae()}),j(Oe,t),Object.assign(Object.assign({},i),{modalList:t,modalInstRefs:e,handleAfterLeave:o})},render(){var t,e;return f(q,null,[this.modalList.map(n=>{var o;return f(ft,Y(n,["destroy","render"],{to:(o=n.to)!==null&&o!==void 0?o:this.to,ref:s=>{s===null?delete this.modalInstRefs[`n-modal-${n.key}`]:this.modalInstRefs[`n-modal-${n.key}`]=s},internalKey:n.key,onInternalAfterLeave:this.handleAfterLeave}),{default:n.render})}),(e=(t=this.$slots).default)===null||e===void 0?void 0:e.call(t)])}}),ht={closeMargin:"16px 12px",closeSize:"20px",closeIconSize:"16px",width:"365px",padding:"16px",titleFontSize:"16px",metaFontSize:"12px",descriptionFontSize:"12px"};function pt(t){const{textColor2:e,successColor:n,infoColor:o,warningColor:s,errorColor:i,popoverColor:r,closeIconColor:c,closeIconColorHover:l,closeIconColorPressed:u,closeColorHover:d,closeColorPressed:h,textColor1:y,textColor3:b,borderRadius:a,fontWeightStrong:m,boxShadow2:C,lineHeight:N,fontSize:k}=t;return Object.assign(Object.assign({},ht),{borderRadius:a,lineHeight:N,fontSize:k,headerFontWeight:m,iconColor:e,iconColorSuccess:n,iconColorInfo:o,iconColorWarning:s,iconColorError:i,color:r,textColor:e,closeIconColor:c,closeIconColorHover:l,closeIconColorPressed:u,closeBorderRadius:a,closeColorHover:d,closeColorPressed:h,headerTextColor:y,descriptionTextColor:b,actionTextColor:e,boxShadow:C})}const gt=Ee({name:"Notification",common:X,peers:{Scrollbar:Be},self:pt}),D=F("n-notification-provider"),mt=z({name:"NotificationContainer",props:{scrollable:{type:Boolean,required:!0},placement:{type:String,required:!0}},setup(){const{mergedThemeRef:t,mergedClsPrefixRef:e,wipTransitionCountRef:n}=O(D),o=w(null);return $e(()=>{var s,i;n.value>0?(s=o?.value)===null||s===void 0||s.classList.add("transitioning"):(i=o?.value)===null||i===void 0||i.classList.remove("transitioning")}),{selfRef:o,mergedTheme:t,mergedClsPrefix:e,transitioning:n}},render(){const{$slots:t,scrollable:e,mergedClsPrefix:n,mergedTheme:o,placement:s}=this;return f("div",{ref:"selfRef",class:[`${n}-notification-container`,e&&`${n}-notification-container--scrollable`,`${n}-notification-container--${s}`]},e?f(Ie,{theme:o.peers.Scrollbar,themeOverrides:o.peerOverrides.Scrollbar,contentStyle:{overflow:"hidden"}},t):t)}}),bt={info:()=>f(Ve,null),success:()=>f(qe,null),warning:()=>f(We,null),error:()=>f(De,null),default:()=>null},V={closable:{type:Boolean,default:!0},type:{type:String,default:"default"},avatar:Function,title:[String,Function],description:[String,Function],content:[String,Function],meta:[String,Function],action:[String,Function],onClose:{type:Function,required:!0},keepAliveOnHover:Boolean,onMouseenter:Function,onMouseleave:Function},xt=Ke(V),yt=z({name:"Notification",props:V,setup(t){const{mergedClsPrefixRef:e,mergedThemeRef:n,props:o}=O(D),{inlineThemeDisabled:s,mergedRtlRef:i}=H(),r=Te("Notification",i,e),c=$(()=>{const{type:u}=t,{self:{color:d,textColor:h,closeIconColor:y,closeIconColorHover:b,closeIconColorPressed:a,headerTextColor:m,descriptionTextColor:C,actionTextColor:N,borderRadius:k,headerFontWeight:R,boxShadow:P,lineHeight:p,fontSize:B,closeMargin:I,closeSize:S,width:ie,padding:re,closeIconSize:ae,closeBorderRadius:se,closeColorHover:le,closeColorPressed:ce,titleFontSize:de,metaFontSize:fe,descriptionFontSize:ue,[Fe("iconColor",u)]:ve},common:{cubicBezierEaseOut:he,cubicBezierEaseIn:pe,cubicBezierEaseInOut:ge}}=n.value,{left:me,right:be,top:xe,bottom:ye}=He(re);return{"--n-color":d,"--n-font-size":B,"--n-text-color":h,"--n-description-text-color":C,"--n-action-text-color":N,"--n-title-text-color":m,"--n-title-font-weight":R,"--n-bezier":ge,"--n-bezier-ease-out":he,"--n-bezier-ease-in":pe,"--n-border-radius":k,"--n-box-shadow":P,"--n-close-border-radius":se,"--n-close-color-hover":le,"--n-close-color-pressed":ce,"--n-close-icon-color":y,"--n-close-icon-color-hover":b,"--n-close-icon-color-pressed":a,"--n-line-height":p,"--n-icon-color":ve,"--n-close-margin":I,"--n-close-size":S,"--n-close-icon-size":ae,"--n-width":ie,"--n-padding-left":me,"--n-padding-right":be,"--n-padding-top":xe,"--n-padding-bottom":ye,"--n-title-font-size":de,"--n-meta-font-size":fe,"--n-description-font-size":ue}}),l=s?J("notification",$(()=>t.type[0]),c,o):void 0;return{mergedClsPrefix:e,showAvatar:$(()=>t.avatar||t.type!=="default"),handleCloseClick(){t.onClose()},rtlEnabled:r,cssVars:s?void 0:c,themeClass:l?.themeClass,onRender:l?.onRender}},render(){var t;const{mergedClsPrefix:e}=this;return(t=this.onRender)===null||t===void 0||t.call(this),f("div",{class:[`${e}-notification-wrapper`,this.themeClass],onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave,style:this.cssVars},f("div",{class:[`${e}-notification`,this.rtlEnabled&&`${e}-notification--rtl`,this.themeClass,{[`${e}-notification--closable`]:this.closable,[`${e}-notification--show-avatar`]:this.showAvatar}],style:this.cssVars},this.showAvatar?f("div",{class:`${e}-notification__avatar`},this.avatar?E(this.avatar):this.type!=="default"?f(_e,{clsPrefix:e},{default:()=>bt[this.type]()}):null):null,this.closable?f(Me,{clsPrefix:e,class:`${e}-notification__close`,onClick:this.handleCloseClick}):null,f("div",{ref:"bodyRef",class:`${e}-notification-main`},this.title?f("div",{class:`${e}-notification-main__header`},E(this.title)):null,this.description?f("div",{class:`${e}-notification-main__description`},E(this.description)):null,this.content?f("pre",{class:`${e}-notification-main__content`},E(this.content)):null,this.meta||this.action?f("div",{class:`${e}-notification-main-footer`},this.meta?f("div",{class:`${e}-notification-main-footer__meta`},E(this.meta)):null,this.action?f("div",{class:`${e}-notification-main-footer__action`},E(this.action)):null):null)))}}),Ct=Object.assign(Object.assign({},V),{duration:Number,onClose:Function,onLeave:Function,onAfterEnter:Function,onAfterLeave:Function,onHide:Function,onAfterShow:Function,onAfterHide:Function}),wt=z({name:"NotificationEnvironment",props:Object.assign(Object.assign({},Ct),{internalKey:{type:String,required:!0},onInternalAfterLeave:{type:Function,required:!0}}),setup(t){const{wipTransitionCountRef:e}=O(D),n=w(!0);let o=null;function s(){n.value=!1,o&&window.clearTimeout(o)}function i(a){e.value++,L(()=>{a.style.height=`${a.offsetHeight}px`,a.style.maxHeight="0",a.style.transition="none",a.offsetHeight,a.style.transition="",a.style.maxHeight=a.style.height})}function r(a){e.value--,a.style.height="",a.style.maxHeight="";const{onAfterEnter:m,onAfterShow:C}=t;m&&m(),C&&C()}function c(a){e.value++,a.style.maxHeight=`${a.offsetHeight}px`,a.style.height=`${a.offsetHeight}px`,a.offsetHeight}function l(a){const{onHide:m}=t;m&&m(),a.style.maxHeight="0",a.offsetHeight}function u(){e.value--;const{onAfterLeave:a,onInternalAfterLeave:m,onAfterHide:C,internalKey:N}=t;a&&a(),m(N),C&&C()}function d(){const{duration:a}=t;a&&(o=window.setTimeout(s,a))}function h(a){a.currentTarget===a.target&&o!==null&&(window.clearTimeout(o),o=null)}function y(a){a.currentTarget===a.target&&d()}function b(){const{onClose:a}=t;a?Promise.resolve(a()).then(m=>{m!==!1&&s()}):s()}return Xe(()=>{t.duration&&(o=window.setTimeout(s,t.duration))}),{show:n,hide:s,handleClose:b,handleAfterLeave:u,handleLeave:l,handleBeforeLeave:c,handleAfterEnter:r,handleBeforeEnter:i,handleMouseenter:h,handleMouseleave:y}},render(){return f(G,{name:"notification-transition",appear:!0,onBeforeEnter:this.handleBeforeEnter,onAfterEnter:this.handleAfterEnter,onBeforeLeave:this.handleBeforeLeave,onLeave:this.handleLeave,onAfterLeave:this.handleAfterLeave},{default:()=>this.show?f(yt,Object.assign({},Ue(this.$props,xt),{onClose:this.handleClose,onMouseenter:this.duration&&this.keepAliveOnHover?this.handleMouseenter:void 0,onMouseleave:this.duration&&this.keepAliveOnHover?this.handleMouseleave:void 0})):null})}}),Pt=x([v("notification-container",`
 z-index: 4000;
 position: fixed;
 overflow: visible;
 display: flex;
 flex-direction: column;
 align-items: flex-end;
 `,[x(">",[v("scrollbar",`
 width: initial;
 overflow: visible;
 height: -moz-fit-content !important;
 height: fit-content !important;
 max-height: 100vh !important;
 `,[x(">",[v("scrollbar-container",`
 height: -moz-fit-content !important;
 height: fit-content !important;
 max-height: 100vh !important;
 `,[v("scrollbar-content",`
 padding-top: 12px;
 padding-bottom: 33px;
 `)])])])]),g("top, top-right, top-left",`
 top: 12px;
 `,[x("&.transitioning >",[v("scrollbar",[x(">",[v("scrollbar-container",`
 min-height: 100vh !important;
 `)])])])]),g("bottom, bottom-right, bottom-left",`
 bottom: 12px;
 `,[x(">",[v("scrollbar",[x(">",[v("scrollbar-container",[v("scrollbar-content",`
 padding-bottom: 12px;
 `)])])])]),v("notification-wrapper",`
 display: flex;
 align-items: flex-end;
 margin-bottom: 0;
 margin-top: 12px;
 `)]),g("top, bottom",`
 left: 50%;
 transform: translateX(-50%);
 `,[v("notification-wrapper",[x("&.notification-transition-enter-from, &.notification-transition-leave-to",`
 transform: scale(0.85);
 `),x("&.notification-transition-leave-from, &.notification-transition-enter-to",`
 transform: scale(1);
 `)])]),g("top",[v("notification-wrapper",`
 transform-origin: top center;
 `)]),g("bottom",[v("notification-wrapper",`
 transform-origin: bottom center;
 `)]),g("top-right, bottom-right",[v("notification",`
 margin-left: 28px;
 margin-right: 16px;
 `)]),g("top-left, bottom-left",[v("notification",`
 margin-left: 16px;
 margin-right: 28px;
 `)]),g("top-right",`
 right: 0;
 `,[T("top-right")]),g("top-left",`
 left: 0;
 `,[T("top-left")]),g("bottom-right",`
 right: 0;
 `,[T("bottom-right")]),g("bottom-left",`
 left: 0;
 `,[T("bottom-left")]),g("scrollable",[g("top-right",`
 top: 0;
 `),g("top-left",`
 top: 0;
 `),g("bottom-right",`
 bottom: 0;
 `),g("bottom-left",`
 bottom: 0;
 `)]),v("notification-wrapper",`
 margin-bottom: 12px;
 `,[x("&.notification-transition-enter-from, &.notification-transition-leave-to",`
 opacity: 0;
 margin-top: 0 !important;
 margin-bottom: 0 !important;
 `),x("&.notification-transition-leave-from, &.notification-transition-enter-to",`
 opacity: 1;
 `),x("&.notification-transition-leave-active",`
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 transform .3s var(--n-bezier-ease-in),
 max-height .3s var(--n-bezier),
 margin-top .3s linear,
 margin-bottom .3s linear,
 box-shadow .3s var(--n-bezier);
 `),x("&.notification-transition-enter-active",`
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 transform .3s var(--n-bezier-ease-out),
 max-height .3s var(--n-bezier),
 margin-top .3s linear,
 margin-bottom .3s linear,
 box-shadow .3s var(--n-bezier);
 `)]),v("notification",`
 background-color: var(--n-color);
 color: var(--n-text-color);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 font-family: inherit;
 font-size: var(--n-font-size);
 font-weight: 400;
 position: relative;
 display: flex;
 overflow: hidden;
 flex-shrink: 0;
 padding-left: var(--n-padding-left);
 padding-right: var(--n-padding-right);
 width: var(--n-width);
 max-width: calc(100vw - 16px - 16px);
 border-radius: var(--n-border-radius);
 box-shadow: var(--n-box-shadow);
 box-sizing: border-box;
 opacity: 1;
 `,[A("avatar",[v("icon",`
 color: var(--n-icon-color);
 `),v("base-icon",`
 color: var(--n-icon-color);
 `)]),g("show-avatar",[v("notification-main",`
 margin-left: 40px;
 width: calc(100% - 40px); 
 `)]),g("closable",[v("notification-main",[x("> *:first-child",`
 padding-right: 20px;
 `)]),A("close",`
 position: absolute;
 top: 0;
 right: 0;
 margin: var(--n-close-margin);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `)]),A("avatar",`
 position: absolute;
 top: var(--n-padding-top);
 left: var(--n-padding-left);
 width: 28px;
 height: 28px;
 font-size: 28px;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[v("icon","transition: color .3s var(--n-bezier);")]),v("notification-main",`
 padding-top: var(--n-padding-top);
 padding-bottom: var(--n-padding-bottom);
 box-sizing: border-box;
 display: flex;
 flex-direction: column;
 margin-left: 8px;
 width: calc(100% - 8px);
 `,[v("notification-main-footer",`
 display: flex;
 align-items: center;
 justify-content: space-between;
 margin-top: 12px;
 `,[A("meta",`
 font-size: var(--n-meta-font-size);
 transition: color .3s var(--n-bezier-ease-out);
 color: var(--n-description-text-color);
 `),A("action",`
 cursor: pointer;
 transition: color .3s var(--n-bezier-ease-out);
 color: var(--n-action-text-color);
 `)]),A("header",`
 font-weight: var(--n-title-font-weight);
 font-size: var(--n-title-font-size);
 transition: color .3s var(--n-bezier-ease-out);
 color: var(--n-title-text-color);
 `),A("description",`
 margin-top: 8px;
 font-size: var(--n-description-font-size);
 white-space: pre-wrap;
 word-wrap: break-word;
 transition: color .3s var(--n-bezier-ease-out);
 color: var(--n-description-text-color);
 `),A("content",`
 line-height: var(--n-line-height);
 margin: 12px 0 0 0;
 font-family: inherit;
 white-space: pre-wrap;
 word-wrap: break-word;
 transition: color .3s var(--n-bezier-ease-out);
 color: var(--n-text-color);
 `,[x("&:first-child","margin: 0;")])])])])]);function T(t){const n=t.split("-")[1]==="left"?"calc(-100%)":"calc(100%)";return v("notification-wrapper",[x("&.notification-transition-enter-from, &.notification-transition-leave-to",`
 transform: translate(${n}, 0);
 `),x("&.notification-transition-leave-from, &.notification-transition-enter-to",`
 transform: translate(0, 0);
 `)])}const oe=F("n-notification-api"),Nt=Object.assign(Object.assign({},K.props),{containerClass:String,containerStyle:[String,Object],to:[String,Object],scrollable:{type:Boolean,default:!0},max:Number,placement:{type:String,default:"top-right"},keepAliveOnHover:Boolean}),St=z({name:"NotificationProvider",props:Nt,setup(t){const{mergedClsPrefixRef:e}=H(t),n=w([]),o={},s=new Set;function i(b){const a=Z(),m=()=>{s.add(a),o[a]&&o[a].hide()},C=ee(Object.assign(Object.assign({},b),{key:a,destroy:m,hide:m,deactivate:m})),{max:N}=t;if(N&&n.value.length-s.size>=N){let k=!1,R=0;for(const P of n.value){if(!s.has(P.key)){o[P.key]&&(P.destroy(),k=!0);break}R++}k||n.value.splice(R,1)}return n.value.push(C),C}const r=["info","success","warning","error"].map(b=>a=>i(Object.assign(Object.assign({},a),{type:b})));function c(b){s.delete(b),n.value.splice(n.value.findIndex(a=>a.key===b),1)}const l=K("Notification","-notification",Pt,gt,t,e),u={create:i,info:r[0],success:r[1],warning:r[2],error:r[3],open:h,destroyAll:y},d=w(0);j(oe,u),j(D,{props:t,mergedClsPrefixRef:e,mergedThemeRef:l,wipTransitionCountRef:d});function h(b){return i(b)}function y(){Object.values(n.value).forEach(b=>{b.hide()})}return Object.assign({mergedClsPrefix:e,notificationList:n,notificationRefs:o,handleAfterLeave:c},u)},render(){var t,e,n;const{placement:o}=this;return f(q,null,(e=(t=this.$slots).default)===null||e===void 0?void 0:e.call(t),this.notificationList.length?f(Q,{to:(n=this.to)!==null&&n!==void 0?n:"body"},f(mt,{class:this.containerClass,style:this.containerStyle,scrollable:this.scrollable&&o!=="top"&&o!=="bottom",placement:o},{default:()=>this.notificationList.map(s=>f(wt,Object.assign({ref:i=>{const r=s.key;i===null?delete this.notificationRefs[r]:this.notificationRefs[r]=i}},Y(s,["destroy","hide","deactivate"]),{internalKey:s.key,onInternalAfterLeave:this.handleAfterLeave,keepAliveOnHover:s.keepAliveOnHover===void 0?this.keepAliveOnHover:s.keepAliveOnHover})))})):null)}});function zt(){const t=O(oe,null);return t===null&&W("use-notification","No outer `n-notification-provider` found."),t}const kt=z({name:"InjectionExtractor",props:{onSetup:Function},setup(t,{slots:e}){var n;return(n=t.onSetup)===null||n===void 0||n.call(t),()=>{var o;return(o=e.default)===null||o===void 0?void 0:o.call(e)}}}),At={message:nt,notification:zt,loadingBar:dt,dialog:ot,modal:Je};function Rt({providersAndProps:t,configProviderProps:e}){let n=Ge(s);const o={app:n};function s(){return f(Qe,U(e),{default:()=>t.map(({type:c,Provider:l,props:u})=>f(l,U(u),{default:()=>f(kt,{onSetup:()=>o[c]=At[c]()})}))})}let i;return Ye&&(i=document.createElement("div"),document.body.appendChild(i),n.mount(i)),Object.assign({unmount:()=>{var c;if(n===null||i===null){Ze("discrete","unmount call no need because discrete app has been unmounted");return}n.unmount(),(c=i.parentNode)===null||c===void 0||c.removeChild(i),i=null,n=null}},o)}function Et(t,{configProviderProps:e,messageProviderProps:n,dialogProviderProps:o,notificationProviderProps:s,loadingBarProviderProps:i,modalProviderProps:r}={}){const c=[];return t.forEach(u=>{switch(u){case"message":c.push({type:u,Provider:tt,props:n});break;case"notification":c.push({type:u,Provider:St,props:s});break;case"dialog":c.push({type:u,Provider:et,props:o});break;case"loadingBar":c.push({type:u,Provider:ct,props:i});break;case"modal":c.push({type:u,Provider:vt,props:r})}}),Rt({providersAndProps:c,configProviderProps:e})}export{$t as NButton,_t as NCard,Qe as NConfigProvider,Mt as NDialog,et as NDialogProvider,Qt as NEmpty,Yt as NForm,Zt as NFormItem,fn as NInput,gn as NInputNumber,ct as NLoadingBarProvider,tt as NMessageProvider,ze as NModal,vt as NModalProvider,St as NNotificationProvider,en as NPopover,xn as NRadio,yn as NRadioGroup,zn as NRate,tn as NSelect,Cn as NSpace,In as NSwitch,kn as NTab,An as NTabPane,Rn as NTabs,nn as NTag,Tt as NxButton,Ft as buttonProps,x as c,v as cB,A as cE,g as cM,Ht as cNotM,Kt as cardProps,X as commonLight,Dt as configProviderProps,Et as createDiscreteApi,un as dateEnUS,Wt as dateZhCN,qt as dialogProps,Vt as dialogProviderProps,on as emptyProps,vn as enUS,rn as formItemProps,an as formProps,mn as inputNumberProps,hn as inputProps,lt as loadingBarProviderProps,Ut as messageProviderProps,ke as modalProps,ut as modalProviderProps,Nt as notificationProviderProps,sn as popoverProps,wn as radioGroupProps,Pn as radioProps,Ln as rateProps,ln as selectProps,Nn as spaceProps,$n as switchProps,jn as tabPaneProps,On as tabProps,En as tabsProps,cn as tagProps,ot as useDialog,dt as useLoadingBar,nt as useMessage,Je as useModal,zt as useNotification,Xt as zhCN,Gt as zindexable};
