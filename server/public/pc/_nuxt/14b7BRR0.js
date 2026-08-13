import{bi as de,bQ as ce,bj as U,bk as i,c5 as A,bl as O,bv as l,bw as E,d as ue,bG as T,ad as a,by as x,c6 as he,bz as be,bn as fe,bo as I,bU as ve,f as L,bp as ge,j as F,bP as D,bq as y,aR as N,aQ as s,S as me}from"./CmYNeFwQ.js";import{a as pe}from"./BvyR7OmS.js";const we={buttonHeightSmall:"14px",buttonHeightMedium:"18px",buttonHeightLarge:"22px",buttonWidthSmall:"14px",buttonWidthMedium:"18px",buttonWidthLarge:"22px",buttonWidthPressedSmall:"20px",buttonWidthPressedMedium:"24px",buttonWidthPressedLarge:"28px",railHeightSmall:"18px",railHeightMedium:"22px",railHeightLarge:"26px",railWidthSmall:"32px",railWidthMedium:"40px",railWidthLarge:"48px"};function xe(e){const{primaryColor:d,opacityDisabled:f,borderRadius:n,textColor3:v}=e;return Object.assign(Object.assign({},we),{iconColor:v,textColor:"white",loadingColor:d,opacityDisabled:f,railColor:"rgba(0, 0, 0, .14)",railColorActive:d,buttonBoxShadow:"0 1px 4px 0 rgba(0, 0, 0, 0.3), inset 0 0 1px 0 rgba(0, 0, 0, 0.05)",buttonColor:"#FFF",railBorderRadiusSmall:n,railBorderRadiusMedium:n,railBorderRadiusLarge:n,buttonBorderRadiusSmall:n,buttonBorderRadiusMedium:n,buttonBorderRadiusLarge:n,boxShadowFocus:`0 0 0 2px ${ce(d,{alpha:.2})}`})}const ye={common:de,self:xe},ke=U("switch",`
 height: var(--n-height);
 min-width: var(--n-width);
 vertical-align: middle;
 user-select: none;
 -webkit-user-select: none;
 display: inline-flex;
 outline: none;
 justify-content: center;
 align-items: center;
`,[i("children-placeholder",`
 height: var(--n-rail-height);
 display: flex;
 flex-direction: column;
 overflow: hidden;
 pointer-events: none;
 visibility: hidden;
 `),i("rail-placeholder",`
 display: flex;
 flex-wrap: none;
 `),i("button-placeholder",`
 width: calc(1.75 * var(--n-rail-height));
 height: var(--n-rail-height);
 `),U("base-loading",`
 position: absolute;
 top: 50%;
 left: 50%;
 transform: translateX(-50%) translateY(-50%);
 font-size: calc(var(--n-button-width) - 4px);
 color: var(--n-loading-color);
 transition: color .3s var(--n-bezier);
 `,[A({left:"50%",top:"50%",originalTransform:"translateX(-50%) translateY(-50%)"})]),i("checked, unchecked",`
 transition: color .3s var(--n-bezier);
 color: var(--n-text-color);
 box-sizing: border-box;
 position: absolute;
 white-space: nowrap;
 top: 0;
 bottom: 0;
 display: flex;
 align-items: center;
 line-height: 1;
 `),i("checked",`
 right: 0;
 padding-right: calc(1.25 * var(--n-rail-height) - var(--n-offset));
 `),i("unchecked",`
 left: 0;
 justify-content: flex-end;
 padding-left: calc(1.25 * var(--n-rail-height) - var(--n-offset));
 `),O("&:focus",[i("rail",`
 box-shadow: var(--n-box-shadow-focus);
 `)]),l("round",[i("rail","border-radius: calc(var(--n-rail-height) / 2);",[i("button","border-radius: calc(var(--n-button-height) / 2);")])]),E("disabled",[E("icon",[l("rubber-band",[l("pressed",[i("rail",[i("button","max-width: var(--n-button-width-pressed);")])]),i("rail",[O("&:active",[i("button","max-width: var(--n-button-width-pressed);")])]),l("active",[l("pressed",[i("rail",[i("button","left: calc(100% - var(--n-offset) - var(--n-button-width-pressed));")])]),i("rail",[O("&:active",[i("button","left: calc(100% - var(--n-offset) - var(--n-button-width-pressed));")])])])])])]),l("active",[i("rail",[i("button","left: calc(100% - var(--n-button-width) - var(--n-offset))")])]),i("rail",`
 overflow: hidden;
 height: var(--n-rail-height);
 min-width: var(--n-rail-width);
 border-radius: var(--n-rail-border-radius);
 cursor: pointer;
 position: relative;
 transition:
 opacity .3s var(--n-bezier),
 background .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 background-color: var(--n-rail-color);
 `,[i("button-icon",`
 color: var(--n-icon-color);
 transition: color .3s var(--n-bezier);
 font-size: calc(var(--n-button-height) - 4px);
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 display: flex;
 justify-content: center;
 align-items: center;
 line-height: 1;
 `,[A()]),i("button",`
 align-items: center; 
 top: var(--n-offset);
 left: var(--n-offset);
 height: var(--n-button-height);
 width: var(--n-button-width-pressed);
 max-width: var(--n-button-width);
 border-radius: var(--n-button-border-radius);
 background-color: var(--n-button-color);
 box-shadow: var(--n-button-box-shadow);
 box-sizing: border-box;
 cursor: inherit;
 content: "";
 position: absolute;
 transition:
 background-color .3s var(--n-bezier),
 left .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 max-width .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 `)]),l("active",[i("rail","background-color: var(--n-rail-color-active);")]),l("loading",[i("rail",`
 cursor: wait;
 `)]),l("disabled",[i("rail",`
 cursor: not-allowed;
 opacity: .5;
 `)])]),Se=Object.assign(Object.assign({},I.props),{size:String,value:{type:[String,Number,Boolean],default:void 0},loading:Boolean,defaultValue:{type:[String,Number,Boolean],default:!1},disabled:{type:Boolean,default:void 0},round:{type:Boolean,default:!0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],checkedValue:{type:[String,Number,Boolean],default:!0},uncheckedValue:{type:[String,Number,Boolean],default:!1},railStyle:Function,rubberBand:{type:Boolean,default:!0},spinProps:Object,onChange:[Function,Array]});let $;const Be=ue({name:"Switch",props:Se,slots:Object,setup(e){$===void 0&&(typeof CSS<"u"?typeof CSS.supports<"u"?$=CSS.supports("width","max(1px)"):$=!1:$=!0);const{mergedClsPrefixRef:d,inlineThemeDisabled:f,mergedComponentPropsRef:n}=fe(e),v=I("Switch","-switch",ke,ye,e,d),g=ve(e,{mergedSize(t){var c,u;if(e.size!==void 0)return e.size;if(t)return t.mergedSize.value;const w=(u=(c=n?.value)===null||c===void 0?void 0:c.Switch)===null||u===void 0?void 0:u.size;return w||"medium"}}),{mergedSizeRef:S,mergedDisabledRef:m}=g,C=L(e.defaultValue),z=me(e,"value"),p=pe(z,C),_=F(()=>p.value===e.checkedValue),o=L(!1),r=L(!1),R=F(()=>{const{railStyle:t}=e;if(t)return t({focused:r.value,checked:_.value})});function V(t){const{"onUpdate:value":c,onChange:u,onUpdateValue:w}=e,{nTriggerFormInput:W,nTriggerFormChange:P}=g;c&&D(c,t),w&&D(w,t),u&&D(u,t),C.value=t,W(),P()}function Q(){const{nTriggerFormFocus:t}=g;t()}function X(){const{nTriggerFormBlur:t}=g;t()}function Y(){e.loading||m.value||(p.value!==e.checkedValue?V(e.checkedValue):V(e.uncheckedValue))}function q(){r.value=!0,Q()}function G(){r.value=!1,X(),o.value=!1}function J(t){e.loading||m.value||t.key===" "&&(p.value!==e.checkedValue?V(e.checkedValue):V(e.uncheckedValue),o.value=!1)}function Z(t){e.loading||m.value||t.key===" "&&(t.preventDefault(),o.value=!0)}const K=F(()=>{const{value:t}=S,{self:{opacityDisabled:c,railColor:u,railColorActive:w,buttonBoxShadow:W,buttonColor:P,boxShadowFocus:ee,loadingColor:te,textColor:ie,iconColor:oe,[y("buttonHeight",t)]:h,[y("buttonWidth",t)]:ae,[y("buttonWidthPressed",t)]:ne,[y("railHeight",t)]:b,[y("railWidth",t)]:B,[y("railBorderRadius",t)]:re,[y("buttonBorderRadius",t)]:le},common:{cubicBezierEaseInOut:se}}=v.value;let j,M,H;return $?(j=`calc((${b} - ${h}) / 2)`,M=`max(${b}, ${h})`,H=`max(${B}, calc(${B} + ${h} - ${b}))`):(j=N((s(b)-s(h))/2),M=N(Math.max(s(b),s(h))),H=s(b)>s(h)?B:N(s(B)+s(h)-s(b))),{"--n-bezier":se,"--n-button-border-radius":le,"--n-button-box-shadow":W,"--n-button-color":P,"--n-button-width":ae,"--n-button-width-pressed":ne,"--n-button-height":h,"--n-height":M,"--n-offset":j,"--n-opacity-disabled":c,"--n-rail-border-radius":re,"--n-rail-color":u,"--n-rail-color-active":w,"--n-rail-height":b,"--n-rail-width":B,"--n-width":H,"--n-box-shadow-focus":ee,"--n-loading-color":te,"--n-text-color":ie,"--n-icon-color":oe}}),k=f?ge("switch",F(()=>S.value[0]),K,e):void 0;return{handleClick:Y,handleBlur:G,handleFocus:q,handleKeyup:J,handleKeydown:Z,mergedRailStyle:R,pressed:o,mergedClsPrefix:d,mergedValue:p,checked:_,mergedDisabled:m,cssVars:f?void 0:K,themeClass:k?.themeClass,onRender:k?.onRender}},render(){const{mergedClsPrefix:e,mergedDisabled:d,checked:f,mergedRailStyle:n,onRender:v,$slots:g}=this;v?.();const{checked:S,unchecked:m,icon:C,"checked-icon":z,"unchecked-icon":p}=g,_=!(T(C)&&T(z)&&T(p));return a("div",{role:"switch","aria-checked":f,class:[`${e}-switch`,this.themeClass,_&&`${e}-switch--icon`,f&&`${e}-switch--active`,d&&`${e}-switch--disabled`,this.round&&`${e}-switch--round`,this.loading&&`${e}-switch--loading`,this.pressed&&`${e}-switch--pressed`,this.rubberBand&&`${e}-switch--rubber-band`],tabindex:this.mergedDisabled?void 0:0,style:this.cssVars,onClick:this.handleClick,onFocus:this.handleFocus,onBlur:this.handleBlur,onKeyup:this.handleKeyup,onKeydown:this.handleKeydown},a("div",{class:`${e}-switch__rail`,"aria-hidden":"true",style:n},x(S,o=>x(m,r=>o||r?a("div",{"aria-hidden":!0,class:`${e}-switch__children-placeholder`},a("div",{class:`${e}-switch__rail-placeholder`},a("div",{class:`${e}-switch__button-placeholder`}),o),a("div",{class:`${e}-switch__rail-placeholder`},a("div",{class:`${e}-switch__button-placeholder`}),r)):null)),a("div",{class:`${e}-switch__button`},x(C,o=>x(z,r=>x(p,R=>a(he,null,{default:()=>this.loading?a(be,Object.assign({key:"loading",clsPrefix:e,strokeWidth:20},this.spinProps)):this.checked&&(r||o)?a("div",{class:`${e}-switch__button-icon`,key:r?"checked-icon":"icon"},r||o):!this.checked&&(R||o)?a("div",{class:`${e}-switch__button-icon`,key:R?"unchecked-icon":"icon"},R||o):null})))),x(S,o=>o&&a("div",{key:"checked",class:`${e}-switch__checked`},o)),x(m,o=>o&&a("div",{key:"unchecked",class:`${e}-switch__unchecked`},o)))))}});export{Be as N,Se as s};
