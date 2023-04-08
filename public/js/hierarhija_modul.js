/*!
 * Vue.js v1.0.27
 * (c) 2016 Evan You
 * Released under the MIT License.
 */
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):t.Vue=e()}(this,function(){"use strict";function t(e,n,r){if(i(e,n))return void(e[n]=r);if(e._isVue)return void t(e._data,n,r);var s=e.__ob__;if(!s)return void(e[n]=r);if(s.convert(n,r),s.dep.notify(),s.vms)for(var o=s.vms.length;o--;){var a=s.vms[o];a._proxy(n),a._digest()}return r}function e(t,e){if(i(t,e)){delete t[e];var n=t.__ob__;if(!n)return void(t._isVue&&(delete t._data[e],t._digest()));if(n.dep.notify(),n.vms)for(var r=n.vms.length;r--;){var s=n.vms[r];s._unproxy(e),s._digest()}}}function i(t,e){return Ii.call(t,e)}function n(t){return Mi.test(t)}function r(t){var e=(t+"").charCodeAt(0);return 36===e||95===e}function s(t){return null==t?"":t.toString()}function o(t){if("string"!=typeof t)return t;var e=Number(t);return isNaN(e)?t:e}function a(t){return"true"===t||"false"!==t&&t}function h(t){var e=t.charCodeAt(0),i=t.charCodeAt(t.length-1);return e!==i||34!==e&&39!==e?t:t.slice(1,-1)}function l(t){return t.replace(Vi,c)}function c(t,e){return e?e.toUpperCase():""}function u(t){return t.replace(Wi,"$1-$2").replace(Wi,"$1-$2").toLowerCase()}function f(t){return t.replace(Bi,c)}function p(t,e){return function(i){var n=arguments.length;return n?n>1?t.apply(e,arguments):t.call(e,i):t.call(e)}}function d(t,e){e=e||0;for(var i=t.length-e,n=new Array(i);i--;)n[i]=t[i+e];return n}function v(t,e){for(var i=Object.keys(e),n=i.length;n--;)t[i[n]]=e[i[n]];return t}function m(t){return null!==t&&"object"==typeof t}function g(t){return zi.call(t)===Ui}function _(t,e,i,n){Object.defineProperty(t,e,{value:i,enumerable:!!n,writable:!0,configurable:!0})}function y(t,e){var i,n,r,s,o,a=function a(){var h=Date.now()-s;h<e&&h>=0?i=setTimeout(a,e-h):(i=null,o=t.apply(r,n),i||(r=n=null))};return function(){return r=this,n=arguments,s=Date.now(),i||(i=setTimeout(a,e)),o}}function b(t,e){for(var i=t.length;i--;)if(t[i]===e)return i;return-1}function w(t){var e=function e(){if(!e.cancelled)return t.apply(this,arguments)};return e.cancel=function(){e.cancelled=!0},e}function C(t,e){return t==e||!(!m(t)||!m(e))&&JSON.stringify(t)===JSON.stringify(e)}function $(t){this.size=0,this.limit=t,this.head=this.tail=void 0,this._keymap=Object.create(null)}function k(){return cn.charCodeAt(pn+1)}function x(){return cn.charCodeAt(++pn)}function A(){return pn>=fn}function O(){for(;k()===An;)x()}function T(t){return t===Cn||t===$n}function N(t){return On[t]}function j(t,e){return Tn[t]===e}function E(){for(var t,e=x();!A();)if(t=x(),t===xn)x();else if(t===e)break}function F(t){for(var e=0,i=t;!A();)if(t=k(),T(t))E();else if(i===t&&e++,j(i,t)&&e--,x(),0===e)break}function S(){for(var t=pn;!A();)if(dn=k(),T(dn))E();else if(N(dn))F(dn);else if(dn===kn){if(x(),dn=k(),dn!==kn){vn!==_n&&vn!==wn||(vn=yn);break}x()}else{if(dn===An&&(vn===bn||vn===wn)){O();break}vn===yn&&(vn=bn),x()}return cn.slice(t+1,pn)||null}function D(){for(var t=[];!A();)t.push(P());return t}function P(){var t,e={};return vn=yn,e.name=S().trim(),vn=wn,t=R(),t.length&&(e.args=t),e}function R(){for(var t=[];!A()&&vn!==yn;){var e=S();if(!e)break;t.push(L(e))}return t}function L(t){if(gn.test(t))return{value:o(t),dynamic:!1};var e=h(t),i=e===t;return{value:i?t:e,dynamic:i}}function H(t){var e=mn.get(t);if(e)return e;cn=t,un={},fn=cn.length,pn=-1,dn="",vn=_n;var i;return cn.indexOf("|")<0?un.expression=cn.trim():(un.expression=S().trim(),i=D(),i.length&&(un.filters=i)),mn.put(t,un),un}function I(t){return t.replace(jn,"\\$&")}function M(){var t=I(Hn.delimiters[0]),e=I(Hn.delimiters[1]),i=I(Hn.unsafeDelimiters[0]),n=I(Hn.unsafeDelimiters[1]);Fn=new RegExp(i+"((?:.|\\n)+?)"+n+"|"+t+"((?:.|\\n)+?)"+e,"g"),Sn=new RegExp("^"+i+"((?:.|\\n)+?)"+n+"$"),En=new $(1e3)}function V(t){En||M();var e=En.get(t);if(e)return e;if(!Fn.test(t))return null;for(var i,n,r,s,o,a,h=[],l=Fn.lastIndex=0;i=Fn.exec(t);)n=i.index,n>l&&h.push({value:t.slice(l,n)}),r=Sn.test(i[0]),s=r?i[1]:i[2],o=s.charCodeAt(0),a=42===o,s=a?s.slice(1):s,h.push({tag:!0,value:s.trim(),html:r,oneTime:a}),l=n+i[0].length;return l<t.length&&h.push({value:t.slice(l)}),En.put(t,h),h}function W(t,e){return t.length>1?t.map(function(t){return B(t,e)}).join("+"):B(t[0],e,!0)}function B(t,e,i){return t.tag?t.oneTime&&e?'"'+e.$eval(t.value)+'"':z(t.value,i):'"'+t.value+'"'}function z(t,e){if(Dn.test(t)){var i=H(t);return i.filters?"this._applyFilters("+i.expression+",null,"+JSON.stringify(i.filters)+",false)":"("+t+")"}return e?t:"("+t+")"}function U(t,e,i,n){Q(t,1,function(){e.appendChild(t)},i,n)}function q(t,e,i,n){Q(t,1,function(){tt(t,e)},i,n)}function J(t,e,i){Q(t,-1,function(){it(t)},e,i)}function Q(t,e,i,n,r){var s=t.__v_trans;if(!s||!s.hooks&&!en||!n._isCompiled||n.$parent&&!n.$parent._isCompiled)return i(),void(r&&r());var o=e>0?"enter":"leave";s[o](i,r)}function G(t){if("string"==typeof t){t=document.querySelector(t)}return t}function Z(t){if(!t)return!1;var e=t.ownerDocument.documentElement,i=t.parentNode;return e===t||e===i||!(!i||1!==i.nodeType||!e.contains(i))}function X(t,e){var i=t.getAttribute(e);return null!==i&&t.removeAttribute(e),i}function Y(t,e){var i=X(t,":"+e);return null===i&&(i=X(t,"v-bind:"+e)),i}function K(t,e){return t.hasAttribute(e)||t.hasAttribute(":"+e)||t.hasAttribute("v-bind:"+e)}function tt(t,e){e.parentNode.insertBefore(t,e)}function et(t,e){e.nextSibling?tt(t,e.nextSibling):e.parentNode.appendChild(t)}function it(t){t.parentNode.removeChild(t)}function nt(t,e){e.firstChild?tt(t,e.firstChild):e.appendChild(t)}function rt(t,e){var i=t.parentNode;i&&i.replaceChild(e,t)}function st(t,e,i,n){t.addEventListener(e,i,n)}function ot(t,e,i){t.removeEventListener(e,i)}function at(t){var e=t.className;return"object"==typeof e&&(e=e.baseVal||""),e}function ht(t,e){Yi&&!/svg$/.test(t.namespaceURI)?t.className=e:t.setAttribute("class",e)}function lt(t,e){if(t.classList)t.classList.add(e);else{var i=" "+at(t)+" ";i.indexOf(" "+e+" ")<0&&ht(t,(i+e).trim())}}function ct(t,e){if(t.classList)t.classList.remove(e);else{for(var i=" "+at(t)+" ",n=" "+e+" ";i.indexOf(n)>=0;)i=i.replace(n," ");ht(t,i.trim())}t.className||t.removeAttribute("class")}function ut(t,e){var i,n;if(dt(t)&&yt(t.content)&&(t=t.content),t.hasChildNodes())for(ft(t),n=e?document.createDocumentFragment():document.createElement("div");i=t.firstChild;)n.appendChild(i);return n}function ft(t){for(var e;e=t.firstChild,pt(e);)t.removeChild(e);for(;e=t.lastChild,pt(e);)t.removeChild(e)}function pt(t){return t&&(3===t.nodeType&&!t.data.trim()||8===t.nodeType)}function dt(t){return t.tagName&&"template"===t.tagName.toLowerCase()}function vt(t,e){var i=Hn.debug?document.createComment(t):document.createTextNode(e?" ":"");return i.__v_anchor=!0,i}function mt(t){if(t.hasAttributes())for(var e=t.attributes,i=0,n=e.length;i<n;i++){var r=e[i].name;if(Vn.test(r))return l(r.replace(Vn,""))}}function gt(t,e,i){for(var n;t!==e;)n=t.nextSibling,i(t),t=n;i(e)}function _t(t,e,i,n,r){function s(){if(a++,o&&a>=h.length){for(var t=0;t<h.length;t++)n.appendChild(h[t]);r&&r()}}var o=!1,a=0,h=[];gt(t,e,function(t){t===e&&(o=!0),h.push(t),J(t,i,s)})}function yt(t){return t&&11===t.nodeType}function bt(t){if(t.outerHTML)return t.outerHTML;var e=document.createElement("div");return e.appendChild(t.cloneNode(!0)),e.innerHTML}function wt(t){var e=t.node;if(t.end)for(;!e.__vue__&&e!==t.end&&e.nextSibling;)e=e.nextSibling;return e.__vue__}function Ct(t,e){var i=t.tagName.toLowerCase(),n=t.hasAttributes();if(Wn.test(i)||Bn.test(i)){if(n)return $t(t,e)}else{if(jt(e,"components",i))return{id:i};var r=n&&$t(t,e);if(r)return r}}function $t(t,e){var i=t.getAttribute("is");if(null!=i){if(jt(e,"components",i))return t.removeAttribute("is"),{id:i}}else if(i=Y(t,"is"),null!=i)return{id:i,dynamic:!0}}function kt(e,n){var r,s,o;for(r in n)s=e[r],o=n[r],i(e,r)?m(s)&&m(o)&&kt(s,o):t(e,r,o);return e}function xt(t,e){var i=Object.create(t||null);return e?v(i,Tt(e)):i}function At(t){if(t.components)for(var e,i=t.components=Tt(t.components),n=Object.keys(i),r=0,s=n.length;r<s;r++){var o=n[r];Wn.test(o)||Bn.test(o)||(e=i[o],g(e)&&(i[o]=Si.extend(e)))}}function Ot(t){var e,i,n=t.props;if(qi(n))for(t.props={},e=n.length;e--;)i=n[e],"string"==typeof i?t.props[i]=null:i.name&&(t.props[i.name]=i);else if(g(n)){var r=Object.keys(n);for(e=r.length;e--;)i=n[r[e]],"function"==typeof i&&(n[r[e]]={type:i})}}function Tt(t){if(qi(t)){for(var e,i={},n=t.length;n--;){e=t[n];var r="function"==typeof e?e.options&&e.options.name||e.id:e.name||e.id;r&&(i[r]=e)}return i}return t}function Nt(t,e,n){function r(i){var r=zn[i]||Un;o[i]=r(t[i],e[i],n,i)}At(e),Ot(e);var s,o={};if(e.extends&&(t="function"==typeof e.extends?Nt(t,e.extends.options,n):Nt(t,e.extends,n)),e.mixins)for(var a=0,h=e.mixins.length;a<h;a++){var l=e.mixins[a],c=l.prototype instanceof Si?l.options:l;t=Nt(t,c,n)}for(s in t)r(s);for(s in e)i(t,s)||r(s);return o}function jt(t,e,i,n){if("string"==typeof i){var r,s=t[e],o=s[i]||s[r=l(i)]||s[r.charAt(0).toUpperCase()+r.slice(1)];return o}}function Et(){this.id=qn++,this.subs=[]}function Ft(t){Zn=!1,t(),Zn=!0}function St(t){if(this.value=t,this.dep=new Et,_(t,"__ob__",this),qi(t)){var e=Ji?Dt:Pt;e(t,Qn,Gn),this.observeArray(t)}else this.walk(t)}function Dt(t,e){t.__proto__=e}function Pt(t,e,i){for(var n=0,r=i.length;n<r;n++){var s=i[n];_(t,s,e[s])}}function Rt(t,e){if(t&&"object"==typeof t){var n;return i(t,"__ob__")&&t.__ob__ instanceof St?n=t.__ob__:Zn&&(qi(t)||g(t))&&Object.isExtensible(t)&&!t._isVue&&(n=new St(t)),n&&e&&n.addVm(e),n}}function Lt(t,e,i){var n=new Et,r=Object.getOwnPropertyDescriptor(t,e);if(!r||r.configurable!==!1){var s=r&&r.get,o=r&&r.set,a=Rt(i);Object.defineProperty(t,e,{enumerable:!0,configurable:!0,get:function(){var e=s?s.call(t):i;if(Et.target&&(n.depend(),a&&a.dep.depend(),qi(e)))for(var r,o=0,h=e.length;o<h;o++)r=e[o],r&&r.__ob__&&r.__ob__.dep.depend();return e},set:function(e){var r=s?s.call(t):i;e!==r&&(o?o.call(t,e):i=e,a=Rt(e),n.notify())}})}}function Ht(t){t.prototype._init=function(t){t=t||{},this.$el=null,this.$parent=t.parent,this.$root=this.$parent?this.$parent.$root:this,this.$children=[],this.$refs={},this.$els={},this._watchers=[],this._directives=[],this._uid=Yn++,this._isVue=!0,this._events={},this._eventsCount={},this._isFragment=!1,this._fragment=this._fragmentStart=this._fragmentEnd=null,this._isCompiled=this._isDestroyed=this._isReady=this._isAttached=this._isBeingDestroyed=this._vForRemoving=!1,this._unlinkFn=null,this._context=t._context||this.$parent,this._scope=t._scope,this._frag=t._frag,this._frag&&this._frag.children.push(this),this.$parent&&this.$parent.$children.push(this),t=this.$options=Nt(this.constructor.options,t,this),this._updateRef(),this._data={},this._callHook("init"),this._initState(),this._initEvents(),this._callHook("created"),t.el&&this.$mount(t.el)}}function It(t){if(void 0===t)return"eof";var e=t.charCodeAt(0);switch(e){case 91:case 93:case 46:case 34:case 39:case 48:return t;case 95:case 36:return"ident";case 32:case 9:case 10:case 13:case 160:case 65279:case 8232:case 8233:return"ws"}return e>=97&&e<=122||e>=65&&e<=90?"ident":e>=49&&e<=57?"number":"else"}function Mt(t){var e=t.trim();return("0"!==t.charAt(0)||!isNaN(t))&&(n(e)?h(e):"*"+e)}function Vt(t){function e(){var e=t[c+1];if(u===lr&&"'"===e||u===cr&&'"'===e)return c++,n="\\"+e,p[tr](),!0}var i,n,r,s,o,a,h,l=[],c=-1,u=rr,f=0,p=[];for(p[er]=function(){void 0!==r&&(l.push(r),r=void 0)},p[tr]=function(){void 0===r?r=n:r+=n},p[ir]=function(){p[tr](),f++},p[nr]=function(){if(f>0)f--,u=hr,p[tr]();else{if(f=0,r=Mt(r),r===!1)return!1;p[er]()}};null!=u;)if(c++,i=t[c],"\\"!==i||!e()){if(s=It(i),h=pr[u],o=h[s]||h.else||fr,o===fr)return;if(u=o[0],a=p[o[1]],a&&(n=o[2],n=void 0===n?i:n,a()===!1))return;if(u===ur)return l.raw=t,l}}function Wt(t){var e=Kn.get(t);return e||(e=Vt(t),e&&Kn.put(t,e)),e}function Bt(t,e){return Yt(e).get(t)}function zt(e,i,n){var r=e;if("string"==typeof i&&(i=Vt(i)),!i||!m(e))return!1;for(var s,o,a=0,h=i.length;a<h;a++)s=e,o=i[a],"*"===o.charAt(0)&&(o=Yt(o.slice(1)).get.call(r,r)),a<h-1?(e=e[o],m(e)||(e={},t(s,o,e))):qi(e)?e.$set(o,n):o in e?e[o]=n:t(e,o,n);return!0}function Ut(){}function qt(t,e){var i=Or.length;return Or[i]=e?t.replace(wr,"\\n"):t,'"'+i+'"'}function Jt(t){var e=t.charAt(0),i=t.slice(1);return gr.test(i)?t:(i=i.indexOf('"')>-1?i.replace($r,Qt):i,e+"scope."+i)}function Qt(t,e){return Or[e]}function Gt(t){yr.test(t),Or.length=0;var e=t.replace(Cr,qt).replace(br,"");return e=(" "+e).replace(xr,Jt).replace($r,Qt),Zt(e)}function Zt(t){try{return new Function("scope","return "+t+";")}catch(t){return Ut}}function Xt(t){var e=Wt(t);if(e)return function(t,i){zt(t,e,i)}}function Yt(t,e){t=t.trim();var i=vr.get(t);if(i)return e&&!i.set&&(i.set=Xt(i.exp)),i;var n={exp:t};return n.get=Kt(t)&&t.indexOf("[")<0?Zt("scope."+t):Gt(t),e&&(n.set=Xt(t)),vr.put(t,n),n}function Kt(t){return kr.test(t)&&!Ar.test(t)&&"Math."!==t.slice(0,5)}function te(){Nr.length=0,jr.length=0,Er={},Fr={},Sr=!1}function ee(){for(var t=!0;t;)t=!1,ie(Nr),ie(jr),Nr.length?t=!0:(Gi&&Hn.devtools&&Gi.emit("flush"),te())}function ie(t){for(var e=0;e<t.length;e++){var i=t[e],n=i.id;Er[n]=null,i.run()}t.length=0}function ne(t){var e=t.id;if(null==Er[e]){var i=t.user?jr:Nr;Er[e]=i.length,i.push(t),Sr||(Sr=!0,an(ee))}}function re(t,e,i,n){n&&v(this,n);var r="function"==typeof e;if(this.vm=t,t._watchers.push(this),this.expression=e,this.cb=i,this.id=++Dr,this.active=!0,this.dirty=this.lazy,this.deps=[],this.newDeps=[],this.depIds=new hn,this.newDepIds=new hn,this.prevError=null,r)this.getter=e,this.setter=void 0;else{var s=Yt(e,this.twoWay);this.getter=s.get,this.setter=s.set}this.value=this.lazy?void 0:this.get(),this.queued=this.shallow=!1}function se(t,e){var i=void 0,n=void 0;e||(e=Pr,e.clear());var r=qi(t),s=m(t);if((r||s)&&Object.isExtensible(t)){if(t.__ob__){var o=t.__ob__.dep.id;if(e.has(o))return;e.add(o)}if(r)for(i=t.length;i--;)se(t[i],e);else if(s)for(n=Object.keys(t),i=n.length;i--;)se(t[n[i]],e)}}function oe(t){return dt(t)&&yt(t.content)}function ae(t,e){var i=e?t:t.trim(),n=Lr.get(i);if(n)return n;var r=document.createDocumentFragment(),s=t.match(Mr),o=Vr.test(t),a=Wr.test(t);if(s||o||a){var h=s&&s[1],l=Ir[h]||Ir.efault,c=l[0],u=l[1],f=l[2],p=document.createElement("div");for(p.innerHTML=u+t+f;c--;)p=p.lastChild;for(var d;d=p.firstChild;)r.appendChild(d)}else r.appendChild(document.createTextNode(t));return e||ft(r),Lr.put(i,r),r}function he(t){if(oe(t))return ae(t.innerHTML);if("SCRIPT"===t.tagName)return ae(t.textContent);for(var e,i=le(t),n=document.createDocumentFragment();e=i.firstChild;)n.appendChild(e);return ft(n),n}function le(t){if(!t.querySelectorAll)return t.cloneNode();var e,i,n,r=t.cloneNode(!0);if(Br){var s=r;if(oe(t)&&(t=t.content,s=r.content),i=t.querySelectorAll("template"),i.length)for(n=s.querySelectorAll("template"),e=n.length;e--;)n[e].parentNode.replaceChild(le(i[e]),n[e])}if(zr)if("TEXTAREA"===t.tagName)r.value=t.value;else if(i=t.querySelectorAll("textarea"),i.length)for(n=r.querySelectorAll("textarea"),e=n.length;e--;)n[e].value=i[e].value;return r}function ce(t,e,i){var n,r;return yt(t)?(ft(t),e?le(t):t):("string"==typeof t?i||"#"!==t.charAt(0)?r=ae(t,i):(r=Hr.get(t),r||(n=document.getElementById(t.slice(1)),n&&(r=he(n),Hr.put(t,r)))):t.nodeType&&(r=he(t)),r&&e?le(r):r)}function ue(t,e,i,n,r,s){this.children=[],this.childFrags=[],this.vm=e,this.scope=r,this.inserted=!1,this.parentFrag=s,s&&s.childFrags.push(this),this.unlink=t(e,i,n,r,this);var o=this.single=1===i.childNodes.length&&!i.childNodes[0].__v_anchor;o?(this.node=i.childNodes[0],this.before=fe,this.remove=pe):(this.node=vt("fragment-start"),this.end=vt("fragment-end"),this.frag=i,nt(this.node,i),i.appendChild(this.end),this.before=de,this.remove=ve),this.node.__v_frag=this}function fe(t,e){this.inserted=!0;var i=e!==!1?q:tt;i(this.node,t,this.vm),Z(this.node)&&this.callHook(me)}function pe(){this.inserted=!1;var t=Z(this.node),e=this;this.beforeRemove(),J(this.node,this.vm,function(){t&&e.callHook(ge),e.destroy()})}function de(t,e){this.inserted=!0;var i=this.vm,n=e!==!1?q:tt;gt(this.node,this.end,function(e){n(e,t,i)}),Z(this.node)&&this.callHook(me)}function ve(){this.inserted=!1;var t=this,e=Z(this.node);this.beforeRemove(),_t(this.node,this.end,this.vm,this.frag,function(){e&&t.callHook(ge),t.destroy()})}function me(t){!t._isAttached&&Z(t.$el)&&t._callHook("attached")}function ge(t){t._isAttached&&!Z(t.$el)&&t._callHook("detached")}function _e(t,e){this.vm=t;var i,n="string"==typeof e;n||dt(e)&&!e.hasAttribute("v-if")?i=ce(e,!0):(i=document.createDocumentFragment(),i.appendChild(e)),this.template=i;var r,s=t.constructor.cid;if(s>0){var o=s+(n?e:bt(e));r=Jr.get(o),r||(r=qe(i,t.$options,!0),Jr.put(o,r))}else r=qe(i,t.$options,!0);this.linker=r}function ye(t,e,i){var n=t.node.previousSibling;if(n){for(t=n.__v_frag;!(t&&t.forId===i&&t.inserted||n===e);){if(n=n.previousSibling,!n)return;t=n.__v_frag}return t}}function be(t){for(var e=-1,i=new Array(Math.floor(t));++e<t;)i[e]=e;return i}function we(t,e,i,n){return n?"$index"===n?t:n.charAt(0).match(/\w/)?Bt(i,n):i[n]:e||i}function Ce(t,e,i){for(var n,r,s,o=e?[]:null,a=0,h=t.options.length;a<h;a++)if(n=t.options[a],s=i?n.hasAttribute("selected"):n.selected){if(r=n.hasOwnProperty("_value")?n._value:n.value,!e)return r;o.push(r)}return o}function $e(t,e){for(var i=t.length;i--;)if(C(t[i],e))return i;return-1}function ke(t,e){var i=e.map(function(t){var e=t.charCodeAt(0);return e>47&&e<58?parseInt(t,10):1===t.length&&(e=t.toUpperCase().charCodeAt(0),e>64&&e<91)?e:ds[t]});return i=[].concat.apply([],i),function(e){if(i.indexOf(e.keyCode)>-1)return t.call(this,e)}}function xe(t){return function(e){return e.stopPropagation(),t.call(this,e)}}function Ae(t){return function(e){return e.preventDefault(),t.call(this,e)}}function Oe(t){return function(e){if(e.target===e.currentTarget)return t.call(this,e)}}function Te(t){if(ys[t])return ys[t];var e=Ne(t);return ys[t]=ys[e]=e,e}function Ne(t){t=u(t);var e=l(t),i=e.charAt(0).toUpperCase()+e.slice(1);bs||(bs=document.createElement("div"));var n,r=ms.length;if("filter"!==e&&e in bs.style)return{kebab:t,camel:e};for(;r--;)if(n=gs[r]+i,n in bs.style)return{kebab:ms[r]+t,camel:n}}function je(t){var e=[];if(qi(t))for(var i=0,n=t.length;i<n;i++){var r=t[i];if(r)if("string"==typeof r)e.push(r);else for(var s in r)r[s]&&e.push(s)}else if(m(t))for(var o in t)t[o]&&e.push(o);return e}function Ee(t,e,i){if(e=e.trim(),e.indexOf(" ")===-1)return void i(t,e);for(var n=e.split(/\s+/),r=0,s=n.length;r<s;r++)i(t,n[r])}function Fe(t,e,i){function n(){++s>=r?i():t[s].call(e,n)}var r=t.length,s=0;t[0].call(e,n)}function Se(t,e,i){for(var r,s,o,a,h,c,f,p=[],d=i.$options.propsData,v=Object.keys(e),m=v.length;m--;)s=v[m],r=e[s]||Rs,h=l(s),Ls.test(h)&&(f={name:s,path:h,options:r,mode:Ps.ONE_WAY,raw:null},o=u(s),null===(a=Y(t,o))&&(null!==(a=Y(t,o+".sync"))?f.mode=Ps.TWO_WAY:null!==(a=Y(t,o+".once"))&&(f.mode=Ps.ONE_TIME)),null!==a?(f.raw=a,c=H(a),a=c.expression,f.filters=c.filters,n(a)&&!c.filters?f.optimizedLiteral=!0:f.dynamic=!0,f.parentPath=a):null!==(a=X(t,o))?f.raw=a:d&&null!==(a=d[s]||d[h])&&(f.raw=a),p.push(f));return De(p)}function De(t){return function(e,n){e._props={};for(var r,s,l,c,f,p=e.$options.propsData,d=t.length;d--;)if(r=t[d],f=r.raw,s=r.path,l=r.options,e._props[s]=r,p&&i(p,s)&&Re(e,r,p[s]),null===f)Re(e,r,void 0);else if(r.dynamic)r.mode===Ps.ONE_TIME?(c=(n||e._context||e).$get(r.parentPath),Re(e,r,c)):e._context?e._bindDir({name:"prop",def:Is,prop:r},null,null,n):Re(e,r,e.$get(r.parentPath));else if(r.optimizedLiteral){var v=h(f);c=v===f?a(o(f)):v,Re(e,r,c)}else c=l.type===Boolean&&(""===f||f===u(r.name))||f,Re(e,r,c)}}function Pe(t,e,i,n){var r=e.dynamic&&Kt(e.parentPath),s=i;void 0===s&&(s=He(t,e)),s=Me(e,s,t);var o=s!==i;Ie(e,s,t)||(s=void 0),r&&!o?Ft(function(){n(s)}):n(s)}function Re(t,e,i){Pe(t,e,i,function(i){Lt(t,e.path,i)})}function Le(t,e,i){Pe(t,e,i,function(i){t[e.path]=i})}function He(t,e){var n=e.options;if(!i(n,"default"))return n.type!==Boolean&&void 0;var r=n.default;return m(r),"function"==typeof r&&n.type!==Function?r.call(t):r}function Ie(t,e,i){if(!t.options.required&&(null===t.raw||null==e))return!0;var n=t.options,r=n.type,s=!r,o=[];if(r){qi(r)||(r=[r]);for(var a=0;a<r.length&&!s;a++){var h=Ve(e,r[a]);o.push(h.expectedType),s=h.valid}}if(!s)return!1;var l=n.validator;return!(l&&!l(e))}function Me(t,e,i){var n=t.options.coerce;return n&&"function"==typeof n?n(e):e}function Ve(t,e){var i,n;return e===String?(n="string",i=typeof t===n):e===Number?(n="number",i=typeof t===n):e===Boolean?(n="boolean",i=typeof t===n):e===Function?(n="function",i=typeof t===n):e===Object?(n="object",i=g(t)):e===Array?(n="array",i=qi(t)):i=t instanceof e,{valid:i,expectedType:n}}function We(t){Ms.push(t),Vs||(Vs=!0,an(Be))}function Be(){for(var t=document.documentElement.offsetHeight,e=0;e<Ms.length;e++)Ms[e]();return Ms=[],Vs=!1,t}function ze(t,e,i,n){this.id=e,this.el=t,this.enterClass=i&&i.enterClass||e+"-enter",this.leaveClass=i&&i.leaveClass||e+"-leave",this.hooks=i,this.vm=n,this.pendingCssEvent=this.pendingCssCb=this.cancel=this.pendingJsCb=this.op=this.cb=null,this.justEntered=!1,this.entered=this.left=!1,this.typeCache={},this.type=i&&i.type;var r=this;["enterNextTick","enterDone","leaveNextTick","leaveDone"].forEach(function(t){r[t]=p(r[t],r)})}function Ue(t){if(/svg$/.test(t.namespaceURI)){var e=t.getBoundingClientRect();return!(e.width||e.height)}return!(t.offsetWidth||t.offsetHeight||t.getClientRects().length)}function qe(t,e,i){var n=i||!e._asComponent?Ke(t,e):null,r=n&&n.terminal||mi(t)||!t.hasChildNodes()?null:si(t.childNodes,e);return function(t,e,i,s,o){var a=d(e.childNodes),h=Je(function(){n&&n(t,e,i,s,o),r&&r(t,a,i,s,o)},t);return Ge(t,h)}}function Je(t,e){e._directives=[];var i=e._directives.length;t();var n=e._directives.slice(i);Qe(n);for(var r=0,s=n.length;r<s;r++)n[r]._bind();return n}function Qe(t){if(0!==t.length){var e,i,n,r,s={};for(e=0,i=t.length;e<i;e++){var o=t[e],a=o.descriptor.def.priority||io,h=s[a];h||(h=s[a]=[]),h.push(o)}var l=0,c=Object.keys(s).sort(function(t,e){return t>e?-1:t===e?0:1});for(e=0,i=c.length;e<i;e++){var u=s[c[e]];for(n=0,r=u.length;n<r;n++)t[l++]=u[n]}}}function Ge(t,e,i,n){function r(r){Ze(t,e,r),i&&n&&Ze(i,n)}return r.dirs=e,r}function Ze(t,e,i){for(var n=e.length;n--;)e[n]._teardown()}function Xe(t,e,i,n){var r=Se(e,i,t),s=Je(function(){r(t,n)},t);return Ge(t,s)}function Ye(t,e,i){var n,r,s=e._containerAttrs,o=e._replacerAttrs;return 11!==t.nodeType&&(e._asComponent?(s&&i&&(n=fi(s,i)),o&&(r=fi(o,e))):r=fi(t.attributes,e)),e._containerAttrs=e._replacerAttrs=null,function(t,e,i){var s,o=t._context;o&&n&&(s=Je(function(){n(o,e,null,i)},o));var a=Je(function(){r&&r(t,e)},t);return Ge(t,a,o,s)}}function Ke(t,e){var i=t.nodeType;return 1!==i||mi(t)?3===i&&t.data.trim()?ei(t,e):null:ti(t,e)}function ti(t,e){if("TEXTAREA"===t.tagName){if(null!==X(t,"v-pre"))return ci;var i=V(t.value);i&&(t.setAttribute(":value",W(i)),t.value="")}var n,r=t.hasAttributes(),s=r&&d(t.attributes);return r&&(n=li(t,s,e)),n||(n=ai(t,e)),n||(n=hi(t,e)),!n&&r&&(n=fi(s,e)),n}function ei(t,e){if(t._skip)return ii;var i=V(t.wholeText);if(!i)return null;for(var n=t.nextSibling;n&&3===n.nodeType;)n._skip=!0,n=n.nextSibling;for(var r,s,o=document.createDocumentFragment(),a=0,h=i.length;a<h;a++)s=i[a],r=s.tag?ni(s,e):document.createTextNode(s.value),o.appendChild(r);return ri(i,o,e)}function ii(t,e){it(e)}function ni(t,e){function i(e){if(!t.descriptor){var i=H(t.value);t.descriptor={name:e,def:Fs[e],expression:i.expression,filters:i.filters}}}var n;return t.oneTime?n=document.createTextNode(t.value):t.html?(n=document.createComment("v-html"),i("html")):(n=document.createTextNode(" "),i("text")),n}function ri(t,e){return function(i,n,r,o){for(var a,h,l,c=e.cloneNode(!0),u=d(c.childNodes),f=0,p=t.length;f<p;f++)a=t[f],h=a.value,a.tag&&(l=u[f],a.oneTime?(h=(o||i).$eval(h),a.html?rt(l,ce(h,!0)):l.data=s(h)):i._bindDir(a.descriptor,l,r,o));rt(n,c)}}function si(t,e){for(var i,n,r,s=[],o=0,a=t.length;o<a;o++)r=t[o],i=Ke(r,e),n=i&&i.terminal||"SCRIPT"===r.tagName||!r.hasChildNodes()?null:si(r.childNodes,e),s.push(i,n);return s.length?oi(s):null}function oi(t){return function(e,i,n,r,s){for(var o,a,h,l=0,c=0,u=t.length;l<u;c++){o=i[c],a=t[l++],h=t[l++];var f=d(o.childNodes);a&&a(e,o,n,r,s),h&&h(e,f,n,r,s)}}}function ai(t,e){var i=t.tagName.toLowerCase();if(!Wn.test(i)){var n=jt(e,"elementDirectives",i);return n?ui(t,i,"",e,n):void 0}}function hi(t,e){var i=Ct(t,e);if(i){var n=mt(t),r={name:"component",ref:n,expression:i.id,def:Zs.component,modifiers:{literal:!i.dynamic}},s=function(t,e,i,s,o){n&&Lt((s||t).$refs,n,null),t._bindDir(r,e,i,s,o)};return s.terminal=!0,s}}function li(t,e,i){if(null!==X(t,"v-pre"))return ci;if(t.hasAttribute("v-else")){var n=t.previousElementSibling;if(n&&n.hasAttribute("v-if"))return ci}for(var r,s,o,a,h,l,c,u,f,p,d=0,v=e.length;d<v;d++)r=e[d],s=r.name.replace(to,""),(h=s.match(Ks))&&(f=jt(i,"directives",h[1]),f&&f.terminal&&(!p||(f.priority||no)>p.priority)&&(p=f,c=r.name,a=pi(r.name),o=r.value,l=h[1],u=h[2]));return p?ui(t,l,o,i,p,c,u,a):void 0}function ci(){}function ui(t,e,i,n,r,s,o,a){var h=H(i),l={name:e,arg:o,expression:h.expression,filters:h.filters,raw:i,attr:s,modifiers:a,def:r};"for"!==e&&"if"!==e&&"router-view"!==e||(l.ref=mt(t));var c=function(t,e,i,n,r){l.ref&&Lt((n||t).$refs,l.ref,null),t._bindDir(l,e,i,n,r)};return c.terminal=!0,c}function fi(t,e){function i(t,e,i){var n=i&&vi(i),r=!n&&H(s);v.push({name:t,attr:o,raw:a,def:e,arg:l,modifiers:c,expression:r&&r.expression,filters:r&&r.filters,interp:i,hasOneTime:n})}for(var n,r,s,o,a,h,l,c,u,f,p,d=t.length,v=[];d--;)if(n=t[d],r=o=n.name,s=a=n.value,f=V(s),l=null,c=pi(r),r=r.replace(to,""),f)s=W(f),l=r,i("bind",Fs.bind,f);else if(eo.test(r))c.literal=!Xs.test(r),i("transition",Zs.transition);else if(Ys.test(r))l=r.replace(Ys,""),i("on",Fs.on);else if(Xs.test(r))h=r.replace(Xs,""),"style"===h||"class"===h?i(h,Zs[h]):(l=h,i("bind",Fs.bind));else if(p=r.match(Ks)){if(h=p[1],l=p[2],"else"===h)continue;u=jt(e,"directives",h,!0),u&&i(h,u)}if(v.length)return di(v)}function pi(t){var e=Object.create(null),i=t.match(to);if(i)for(var n=i.length;n--;)e[i[n].slice(1)]=!0;return e}function di(t){return function(e,i,n,r,s){for(var o=t.length;o--;)e._bindDir(t[o],i,n,r,s)}}function vi(t){for(var e=t.length;e--;)if(t[e].oneTime)return!0}function mi(t){return"SCRIPT"===t.tagName&&(!t.hasAttribute("type")||"text/javascript"===t.getAttribute("type"))}function gi(t,e){return e&&(e._containerAttrs=yi(t)),dt(t)&&(t=ce(t)),e&&(e._asComponent&&!e.template&&(e.template="<slot></slot>"),e.template&&(e._content=ut(t),t=_i(t,e))),yt(t)&&(nt(vt("v-start",!0),t),t.appendChild(vt("v-end",!0))),t}function _i(t,e){var i=e.template,n=ce(i,!0);if(n){var r=n.firstChild;if(!r)return n;var s=r.tagName&&r.tagName.toLowerCase();return e.replace?(t===document.body,n.childNodes.length>1||1!==r.nodeType||"component"===s||jt(e,"components",s)||K(r,"is")||jt(e,"elementDirectives",s)||r.hasAttribute("v-for")||r.hasAttribute("v-if")?n:(e._replacerAttrs=yi(r),bi(t,r),r)):(t.appendChild(n),t)}}function yi(t){if(1===t.nodeType&&t.hasAttributes())return d(t.attributes)}function bi(t,e){for(var i,n,r=t.attributes,s=r.length;s--;)i=r[s].name,n=r[s].value,e.hasAttribute(i)||ro.test(i)?"class"===i&&!V(n)&&(n=n.trim())&&n.split(/\s+/).forEach(function(t){lt(e,t)}):e.setAttribute(i,n)}function wi(t,e){if(e){for(var i,n,r=t._slotContents=Object.create(null),s=0,o=e.children.length;s<o;s++)i=e.children[s],(n=i.getAttribute("slot"))&&(r[n]||(r[n]=[])).push(i);for(n in r)r[n]=Ci(r[n],e);if(e.hasChildNodes()){var a=e.childNodes;if(1===a.length&&3===a[0].nodeType&&!a[0].data.trim())return;r.default=Ci(e.childNodes,e)}}}function Ci(t,e){var i=document.createDocumentFragment();t=d(t);for(var n=0,r=t.length;n<r;n++){var s=t[n];!dt(s)||s.hasAttribute("v-if")||s.hasAttribute("v-for")||(e.removeChild(s),s=ce(s,!0)),i.appendChild(s)}return i}function $i(t){function e(){}function n(t,e){var i=new re(e,t,null,{lazy:!0});return function(){return i.dirty&&i.evaluate(),Et.target&&i.depend(),i.value}}Object.defineProperty(t.prototype,"$data",{get:function(){return this._data},set:function(t){t!==this._data&&this._setData(t)}}),t.prototype._initState=function(){this._initProps(),this._initMeta(),this._initMethods(),this._initData(),this._initComputed()},t.prototype._initProps=function(){var t=this.$options,e=t.el,i=t.props;e=t.el=G(e),this._propsUnlinkFn=e&&1===e.nodeType&&i?Xe(this,e,i,this._scope):null},t.prototype._initData=function(){var t=this.$options.data,e=this._data=t?t():{};g(e)||(e={});var n,r,s=this._props,o=Object.keys(e);for(n=o.length;n--;)r=o[n],s&&i(s,r)||this._proxy(r);Rt(e,this)},t.prototype._setData=function(t){t=t||{};var e=this._data;this._data=t;var n,r,s;for(n=Object.keys(e),s=n.length;s--;)r=n[s],r in t||this._unproxy(r);for(n=Object.keys(t),s=n.length;s--;)r=n[s],i(this,r)||this._proxy(r);e.__ob__.removeVm(this),Rt(t,this),this._digest()},t.prototype._proxy=function(t){if(!r(t)){var e=this;Object.defineProperty(e,t,{configurable:!0,enumerable:!0,get:function(){return e._data[t]},set:function(i){e._data[t]=i}})}},t.prototype._unproxy=function(t){r(t)||delete this[t]},t.prototype._digest=function(){for(var t=0,e=this._watchers.length;t<e;t++)this._watchers[t].update(!0)},t.prototype._initComputed=function(){var t=this.$options.computed;if(t)for(var i in t){var r=t[i],s={enumerable:!0,configurable:!0};"function"==typeof r?(s.get=n(r,this),s.set=e):(s.get=r.get?r.cache!==!1?n(r.get,this):p(r.get,this):e,s.set=r.set?p(r.set,this):e),Object.defineProperty(this,i,s)}},t.prototype._initMethods=function(){var t=this.$options.methods;if(t)for(var e in t)this[e]=p(t[e],this)},t.prototype._initMeta=function(){var t=this.$options._meta;if(t)for(var e in t)Lt(this,e,t[e])}}function ki(t){function e(t,e){for(var i,n,r,s=e.attributes,o=0,a=s.length;o<a;o++)i=s[o].name,oo.test(i)&&(i=i.replace(oo,""),n=s[o].value,Kt(n)&&(n+=".apply(this, $arguments)"),r=(t._scope||t._context).$eval(n,!0),r._fromParent=!0,t.$on(i.replace(oo),r))}function i(t,e,i){if(i){var r,s,o,a;for(s in i)if(r=i[s],qi(r))for(o=0,a=r.length;o<a;o++)n(t,e,s,r[o]);else n(t,e,s,r)}}function n(t,e,i,r,s){var o=typeof r;if("function"===o)t[e](i,r,s);else if("string"===o){var a=t.$options.methods,h=a&&a[r];h&&t[e](i,h,s)}else r&&"object"===o&&n(t,e,i,r.handler,r)}function r(){this._isAttached||(this._isAttached=!0,this.$children.forEach(s))}function s(t){!t._isAttached&&Z(t.$el)&&t._callHook("attached")}function o(){this._isAttached&&(this._isAttached=!1,this.$children.forEach(a))}function a(t){t._isAttached&&!Z(t.$el)&&t._callHook("detached")}t.prototype._initEvents=function(){var t=this.$options;t._asComponent&&e(this,t.el),i(this,"$on",t.events),i(this,"$watch",t.watch)},t.prototype._initDOMHooks=function(){this.$on("hook:attached",r),this.$on("hook:detached",o)},t.prototype._callHook=function(t){this.$emit("pre-hook:"+t);var e=this.$options[t];if(e)for(var i=0,n=e.length;i<n;i++)e[i].call(this);this.$emit("hook:"+t)}}function xi(){}function Ai(t,e,i,n,r,s){this.vm=e,this.el=i,this.descriptor=t,this.name=t.name,this.expression=t.expression,this.arg=t.arg,this.modifiers=t.modifiers,this.filters=t.filters,this.literal=this.modifiers&&this.modifiers.literal,this._locked=!1,this._bound=!1,this._listeners=null,this._host=n,this._scope=r,this._frag=s}function Oi(t){t.prototype._updateRef=function(t){var e=this.$options._ref;if(e){var i=(this._scope||this._context).$refs;t?i[e]===this&&(i[e]=null):i[e]=this}},t.prototype._compile=function(t){var e=this.$options,i=t;if(t=gi(t,e),this._initElement(t),1!==t.nodeType||null===X(t,"v-pre")){var n=this._context&&this._context.$options,r=Ye(t,e,n);wi(this,e._content);var s,o=this.constructor;e._linkerCachable&&(s=o.linker,s||(s=o.linker=qe(t,e)));var a=r(this,t,this._scope),h=s?s(this,t):qe(t,e)(this,t);this._unlinkFn=function(){a(),h(!0)},
e.replace&&rt(i,t),this._isCompiled=!0,this._callHook("compiled")}},t.prototype._initElement=function(t){yt(t)?(this._isFragment=!0,this.$el=this._fragmentStart=t.firstChild,this._fragmentEnd=t.lastChild,3===this._fragmentStart.nodeType&&(this._fragmentStart.data=this._fragmentEnd.data=""),this._fragment=t):this.$el=t,this.$el.__vue__=this,this._callHook("beforeCompile")},t.prototype._bindDir=function(t,e,i,n,r){this._directives.push(new Ai(t,this,e,i,n,r))},t.prototype._destroy=function(t,e){if(this._isBeingDestroyed)return void(e||this._cleanup());var i,n,r=this,s=function(){!i||n||e||r._cleanup()};t&&this.$el&&(n=!0,this.$remove(function(){n=!1,s()})),this._callHook("beforeDestroy"),this._isBeingDestroyed=!0;var o,a=this.$parent;for(a&&!a._isBeingDestroyed&&(a.$children.$remove(this),this._updateRef(!0)),o=this.$children.length;o--;)this.$children[o].$destroy();for(this._propsUnlinkFn&&this._propsUnlinkFn(),this._unlinkFn&&this._unlinkFn(),o=this._watchers.length;o--;)this._watchers[o].teardown();this.$el&&(this.$el.__vue__=null),i=!0,s()},t.prototype._cleanup=function(){this._isDestroyed||(this._frag&&this._frag.children.$remove(this),this._data&&this._data.__ob__&&this._data.__ob__.removeVm(this),this.$el=this.$parent=this.$root=this.$children=this._watchers=this._context=this._scope=this._directives=null,this._isDestroyed=!0,this._callHook("destroyed"),this.$off())}}function Ti(t){t.prototype._applyFilters=function(t,e,i,n){var r,s,o,a,h,l,c,u,f;for(l=0,c=i.length;l<c;l++)if(r=i[n?c-l-1:l],s=jt(this.$options,"filters",r.name,!0),s&&(s=n?s.write:s.read||s,"function"==typeof s)){if(o=n?[t,e]:[t],h=n?2:1,r.args)for(u=0,f=r.args.length;u<f;u++)a=r.args[u],o[u+h]=a.dynamic?this.$get(a.value):a.value;t=s.apply(this,o)}return t},t.prototype._resolveComponent=function(e,i){var n;if(n="function"==typeof e?e:jt(this.$options,"components",e,!0))if(n.options)i(n);else if(n.resolved)i(n.resolved);else if(n.requested)n.pendingCallbacks.push(i);else{n.requested=!0;var r=n.pendingCallbacks=[i];n.call(this,function(e){g(e)&&(e=t.extend(e)),n.resolved=e;for(var i=0,s=r.length;i<s;i++)r[i](e)},function(t){})}}}function Ni(t){function i(t){return JSON.parse(JSON.stringify(t))}t.prototype.$get=function(t,e){var i=Yt(t);if(i){if(e){var n=this;return function(){n.$arguments=d(arguments);var t=i.get.call(n,n);return n.$arguments=null,t}}try{return i.get.call(this,this)}catch(t){}}},t.prototype.$set=function(t,e){var i=Yt(t,!0);i&&i.set&&i.set.call(this,this,e)},t.prototype.$delete=function(t){e(this._data,t)},t.prototype.$watch=function(t,e,i){var n,r=this;"string"==typeof t&&(n=H(t),t=n.expression);var s=new re(r,t,e,{deep:i&&i.deep,sync:i&&i.sync,filters:n&&n.filters,user:!i||i.user!==!1});return i&&i.immediate&&e.call(r,s.value),function(){s.teardown()}},t.prototype.$eval=function(t,e){if(ao.test(t)){var i=H(t),n=this.$get(i.expression,e);return i.filters?this._applyFilters(n,null,i.filters):n}return this.$get(t,e)},t.prototype.$interpolate=function(t){var e=V(t),i=this;return e?1===e.length?i.$eval(e[0].value)+"":e.map(function(t){return t.tag?i.$eval(t.value):t.value}).join(""):t},t.prototype.$log=function(t){var e=t?Bt(this._data,t):this._data;if(e&&(e=i(e)),!t){var n;for(n in this.$options.computed)e[n]=i(this[n]);if(this._props)for(n in this._props)e[n]=i(this[n])}console.log(e)}}function ji(t){function e(t,e,n,r,s,o){e=i(e);var a=!Z(e),h=r===!1||a?s:o,l=!a&&!t._isAttached&&!Z(t.$el);return t._isFragment?(gt(t._fragmentStart,t._fragmentEnd,function(i){h(i,e,t)}),n&&n()):h(t.$el,e,t,n),l&&t._callHook("attached"),t}function i(t){return"string"==typeof t?document.querySelector(t):t}function n(t,e,i,n){e.appendChild(t),n&&n()}function r(t,e,i,n){tt(t,e),n&&n()}function s(t,e,i){it(t),i&&i()}t.prototype.$nextTick=function(t){an(t,this)},t.prototype.$appendTo=function(t,i,r){return e(this,t,i,r,n,U)},t.prototype.$prependTo=function(t,e,n){return t=i(t),t.hasChildNodes()?this.$before(t.firstChild,e,n):this.$appendTo(t,e,n),this},t.prototype.$before=function(t,i,n){return e(this,t,i,n,r,q)},t.prototype.$after=function(t,e,n){return t=i(t),t.nextSibling?this.$before(t.nextSibling,e,n):this.$appendTo(t.parentNode,e,n),this},t.prototype.$remove=function(t,e){if(!this.$el.parentNode)return t&&t();var i=this._isAttached&&Z(this.$el);i||(e=!1);var n=this,r=function(){i&&n._callHook("detached"),t&&t()};if(this._isFragment)_t(this._fragmentStart,this._fragmentEnd,this,this._fragment,r);else{var o=e===!1?s:J;o(this.$el,this,r)}return this}}function Ei(t){function e(t,e,n){var r=t.$parent;if(r&&n&&!i.test(e))for(;r;)r._eventsCount[e]=(r._eventsCount[e]||0)+n,r=r.$parent}t.prototype.$on=function(t,i){return(this._events[t]||(this._events[t]=[])).push(i),e(this,t,1),this},t.prototype.$once=function(t,e){function i(){n.$off(t,i),e.apply(this,arguments)}var n=this;return i.fn=e,this.$on(t,i),this},t.prototype.$off=function(t,i){var n;if(!arguments.length){if(this.$parent)for(t in this._events)n=this._events[t],n&&e(this,t,-n.length);return this._events={},this}if(n=this._events[t],!n)return this;if(1===arguments.length)return e(this,t,-n.length),this._events[t]=null,this;for(var r,s=n.length;s--;)if(r=n[s],r===i||r.fn===i){e(this,t,-1),n.splice(s,1);break}return this},t.prototype.$emit=function(t){var e="string"==typeof t;t=e?t:t.name;var i=this._events[t],n=e||!i;if(i){i=i.length>1?d(i):i;var r=e&&i.some(function(t){return t._fromParent});r&&(n=!1);for(var s=d(arguments,1),o=0,a=i.length;o<a;o++){var h=i[o],l=h.apply(this,s);l!==!0||r&&!h._fromParent||(n=!0)}}return n},t.prototype.$broadcast=function(t){var e="string"==typeof t;if(t=e?t:t.name,this._eventsCount[t]){var i=this.$children,n=d(arguments);e&&(n[0]={name:t,source:this});for(var r=0,s=i.length;r<s;r++){var o=i[r],a=o.$emit.apply(o,n);a&&o.$broadcast.apply(o,n)}return this}},t.prototype.$dispatch=function(t){var e=this.$emit.apply(this,arguments);if(e){var i=this.$parent,n=d(arguments);for(n[0]={name:t,source:this};i;)e=i.$emit.apply(i,n),i=e?i.$parent:null;return this}};var i=/^hook:/}function Fi(t){function e(){this._isAttached=!0,this._isReady=!0,this._callHook("ready")}t.prototype.$mount=function(t){if(!this._isCompiled)return t=G(t),t||(t=document.createElement("div")),this._compile(t),this._initDOMHooks(),Z(this.$el)?(this._callHook("attached"),e.call(this)):this.$once("hook:attached",e),this},t.prototype.$destroy=function(t,e){this._destroy(t,e)},t.prototype.$compile=function(t,e,i,n){return qe(t,this.$options,!0)(this,t,e,i,n)}}function Si(t){this._init(t)}function Di(t,e,i){return i=i?parseInt(i,10):0,e=o(e),"number"==typeof e?t.slice(i,i+e):t}function Pi(t,e,i){if(t=uo(t),null==e)return t;if("function"==typeof e)return t.filter(e);e=(""+e).toLowerCase();for(var n,r,s,o,a="in"===i?3:2,h=Array.prototype.concat.apply([],d(arguments,a)),l=[],c=0,u=t.length;c<u;c++)if(n=t[c],s=n&&n.$value||n,o=h.length){for(;o--;)if(r=h[o],"$key"===r&&Li(n.$key,e)||Li(Bt(s,r),e)){l.push(n);break}}else Li(n,e)&&l.push(n);return l}function Ri(t){function e(t,e,i){var r=n[i];return r&&("$key"!==r&&(m(t)&&"$value"in t&&(t=t.$value),m(e)&&"$value"in e&&(e=e.$value)),t=m(t)?Bt(t,r):t,e=m(e)?Bt(e,r):e),t===e?0:t>e?s:-s}var i=null,n=void 0;t=uo(t);var r=d(arguments,1),s=r[r.length-1];"number"==typeof s?(s=s<0?-1:1,r=r.length>1?r.slice(0,-1):r):s=1;var o=r[0];return o?("function"==typeof o?i=function(t,e){return o(t,e)*s}:(n=Array.prototype.concat.apply([],r),i=function(t,r,s){return s=s||0,s>=n.length-1?e(t,r,s):e(t,r,s)||i(t,r,s+1)}),t.slice().sort(i)):t}function Li(t,e){var i;if(g(t)){var n=Object.keys(t);for(i=n.length;i--;)if(Li(t[n[i]],e))return!0}else if(qi(t)){for(i=t.length;i--;)if(Li(t[i],e))return!0}else if(null!=t)return t.toString().toLowerCase().indexOf(e)>-1}function Hi(i){function n(t){return new Function("return function "+f(t)+" (options) { this._init(options) }")()}i.options={directives:Fs,elementDirectives:co,filters:po,transitions:{},components:{},partials:{},replace:!0},i.util=Xn,i.config=Hn,i.set=t,i.delete=e,i.nextTick=an,i.compiler=so,i.FragmentFactory=_e,i.internalDirectives=Zs,i.parsers={path:dr,text:Pn,template:Ur,directive:Nn,expression:Tr},i.cid=0;var r=1;i.extend=function(t){t=t||{};var e=this,i=0===e.cid;if(i&&t._Ctor)return t._Ctor;var s=t.name||e.options.name,o=n(s||"VueComponent");return o.prototype=Object.create(e.prototype),o.prototype.constructor=o,o.cid=r++,o.options=Nt(e.options,t),o.super=e,o.extend=e.extend,Hn._assetTypes.forEach(function(t){o[t]=e[t]}),s&&(o.options.components[s]=o),i&&(t._Ctor=o),o},i.use=function(t){if(!t.installed){var e=d(arguments,1);return e.unshift(this),"function"==typeof t.install?t.install.apply(t,e):t.apply(null,e),t.installed=!0,this}},i.mixin=function(t){i.options=Nt(i.options,t)},Hn._assetTypes.forEach(function(t){i[t]=function(e,n){return n?("component"===t&&g(n)&&(n.name||(n.name=e),n=i.extend(n)),this.options[t+"s"][e]=n,n):this.options[t+"s"][e]}}),v(i.transition,Mn)}var Ii=Object.prototype.hasOwnProperty,Mi=/^\s?(true|false|-?[\d\.]+|'[^']*'|"[^"]*")\s?$/,Vi=/-(\w)/g,Wi=/([^-])([A-Z])/g,Bi=/(?:^|[-_\/])(\w)/g,zi=Object.prototype.toString,Ui="[object Object]",qi=Array.isArray,Ji="__proto__"in{},Qi="undefined"!=typeof window&&"[object Object]"!==Object.prototype.toString.call(window),Gi=Qi&&window.__VUE_DEVTOOLS_GLOBAL_HOOK__,Zi=Qi&&window.navigator.userAgent.toLowerCase(),Xi=Zi&&Zi.indexOf("trident")>0,Yi=Zi&&Zi.indexOf("msie 9.0")>0,Ki=Zi&&Zi.indexOf("android")>0,tn=void 0,en=void 0,nn=void 0,rn=void 0;if(Qi&&!Yi){var sn=void 0===window.ontransitionend&&void 0!==window.onwebkittransitionend,on=void 0===window.onanimationend&&void 0!==window.onwebkitanimationend;tn=sn?"WebkitTransition":"transition",en=sn?"webkitTransitionEnd":"transitionend",nn=on?"WebkitAnimation":"animation",rn=on?"webkitAnimationEnd":"animationend"}var an=function(){function t(){n=!1;var t=i.slice(0);i=[];for(var e=0;e<t.length;e++)t[e]()}var e,i=[],n=!1;return!Qi||!window.postMessage||window.importScripts||Ki&&!window.requestAnimationFrame?e="undefined"!=typeof global&&global.setImmediate||setTimeout:!function(){var i="__vue__nextTick__";window.addEventListener("message",function(e){e.source===window&&e.data===i&&t()}),e=function(){window.postMessage(i,"*")}}(),function(r,s){var o=s?function(){r.call(s)}:r;i.push(o),n||(n=!0,e(t,0))}}(),hn=void 0;"undefined"!=typeof Set&&Set.toString().match(/native code/)?hn=Set:(hn=function(){this.set=Object.create(null)},hn.prototype.has=function(t){return void 0!==this.set[t]},hn.prototype.add=function(t){this.set[t]=1},hn.prototype.clear=function(){this.set=Object.create(null)});var ln=$.prototype;ln.put=function(t,e){var i,n=this.get(t,!0);return n||(this.size===this.limit&&(i=this.shift()),n={key:t},this._keymap[t]=n,this.tail?(this.tail.newer=n,n.older=this.tail):this.head=n,this.tail=n,this.size++),n.value=e,i},ln.shift=function(){var t=this.head;return t&&(this.head=this.head.newer,this.head.older=void 0,t.newer=t.older=void 0,this._keymap[t.key]=void 0,this.size--),t},ln.get=function(t,e){var i=this._keymap[t];if(void 0!==i)return i===this.tail?e?i:i.value:(i.newer&&(i===this.head&&(this.head=i.newer),i.newer.older=i.older),i.older&&(i.older.newer=i.newer),i.newer=void 0,i.older=this.tail,this.tail&&(this.tail.newer=i),this.tail=i,e?i:i.value)};var cn,un,fn,pn,dn,vn,mn=new $(1e3),gn=/^in$|^-?\d+/,_n=0,yn=1,bn=2,wn=3,Cn=34,$n=39,kn=124,xn=92,An=32,On={91:1,123:1,40:1},Tn={91:93,123:125,40:41},Nn=Object.freeze({parseDirective:H}),jn=/[-.*+?^${}()|[\]\/\\]/g,En=void 0,Fn=void 0,Sn=void 0,Dn=/[^|]\|[^|]/,Pn=Object.freeze({compileRegex:M,parseText:V,tokensToExp:W}),Rn=["{{","}}"],Ln=["{{{","}}}"],Hn=Object.defineProperties({debug:!1,silent:!1,async:!0,warnExpressionErrors:!0,devtools:!1,_delimitersChanged:!0,_assetTypes:["component","directive","elementDirective","filter","transition","partial"],_propBindingModes:{ONE_WAY:0,TWO_WAY:1,ONE_TIME:2},_maxUpdateCount:100},{delimiters:{get:function(){return Rn},set:function(t){Rn=t,M()},configurable:!0,enumerable:!0},unsafeDelimiters:{get:function(){return Ln},set:function(t){Ln=t,M()},configurable:!0,enumerable:!0}}),In=void 0,Mn=Object.freeze({appendWithTransition:U,beforeWithTransition:q,removeWithTransition:J,applyTransition:Q}),Vn=/^v-ref:/,Wn=/^(div|p|span|img|a|b|i|br|ul|ol|li|h1|h2|h3|h4|h5|h6|code|pre|table|th|td|tr|form|label|input|select|option|nav|article|section|header|footer)$/i,Bn=/^(slot|partial|component)$/i,zn=Hn.optionMergeStrategies=Object.create(null);zn.data=function(t,e,i){return i?t||e?function(){var n="function"==typeof e?e.call(i):e,r="function"==typeof t?t.call(i):void 0;return n?kt(n,r):r}:void 0:e?"function"!=typeof e?t:t?function(){return kt(e.call(this),t.call(this))}:e:t},zn.el=function(t,e,i){if(i||!e||"function"==typeof e){var n=e||t;return i&&"function"==typeof n?n.call(i):n}},zn.init=zn.created=zn.ready=zn.attached=zn.detached=zn.beforeCompile=zn.compiled=zn.beforeDestroy=zn.destroyed=zn.activate=function(t,e){return e?t?t.concat(e):qi(e)?e:[e]:t},Hn._assetTypes.forEach(function(t){zn[t+"s"]=xt}),zn.watch=zn.events=function(t,e){if(!e)return t;if(!t)return e;var i={};v(i,t);for(var n in e){var r=i[n],s=e[n];r&&!qi(r)&&(r=[r]),i[n]=r?r.concat(s):[s]}return i},zn.props=zn.methods=zn.computed=function(t,e){if(!e)return t;if(!t)return e;var i=Object.create(null);return v(i,t),v(i,e),i};var Un=function(t,e){return void 0===e?t:e},qn=0;Et.target=null,Et.prototype.addSub=function(t){this.subs.push(t)},Et.prototype.removeSub=function(t){this.subs.$remove(t)},Et.prototype.depend=function(){Et.target.addDep(this)},Et.prototype.notify=function(){for(var t=d(this.subs),e=0,i=t.length;e<i;e++)t[e].update()};var Jn=Array.prototype,Qn=Object.create(Jn);["push","pop","shift","unshift","splice","sort","reverse"].forEach(function(t){var e=Jn[t];_(Qn,t,function(){for(var i=arguments.length,n=new Array(i);i--;)n[i]=arguments[i];var r,s=e.apply(this,n),o=this.__ob__;switch(t){case"push":r=n;break;case"unshift":r=n;break;case"splice":r=n.slice(2)}return r&&o.observeArray(r),o.dep.notify(),s})}),_(Jn,"$set",function(t,e){return t>=this.length&&(this.length=Number(t)+1),this.splice(t,1,e)[0]}),_(Jn,"$remove",function(t){if(this.length){var e=b(this,t);return e>-1?this.splice(e,1):void 0}});var Gn=Object.getOwnPropertyNames(Qn),Zn=!0;St.prototype.walk=function(t){for(var e=Object.keys(t),i=0,n=e.length;i<n;i++)this.convert(e[i],t[e[i]])},St.prototype.observeArray=function(t){for(var e=0,i=t.length;e<i;e++)Rt(t[e])},St.prototype.convert=function(t,e){Lt(this.value,t,e)},St.prototype.addVm=function(t){(this.vms||(this.vms=[])).push(t)},St.prototype.removeVm=function(t){this.vms.$remove(t)};var Xn=Object.freeze({defineReactive:Lt,set:t,del:e,hasOwn:i,isLiteral:n,isReserved:r,_toString:s,toNumber:o,toBoolean:a,stripQuotes:h,camelize:l,hyphenate:u,classify:f,bind:p,toArray:d,extend:v,isObject:m,isPlainObject:g,def:_,debounce:y,indexOf:b,cancellable:w,looseEqual:C,isArray:qi,hasProto:Ji,inBrowser:Qi,devtools:Gi,isIE:Xi,isIE9:Yi,isAndroid:Ki,get transitionProp(){return tn},get transitionEndEvent(){return en},get animationProp(){return nn},get animationEndEvent(){return rn},nextTick:an,get _Set(){return hn},query:G,inDoc:Z,getAttr:X,getBindAttr:Y,hasBindAttr:K,before:tt,after:et,remove:it,prepend:nt,replace:rt,on:st,off:ot,setClass:ht,addClass:lt,removeClass:ct,extractContent:ut,trimNode:ft,isTemplate:dt,createAnchor:vt,findRef:mt,mapNodeRange:gt,removeNodeRange:_t,isFragment:yt,getOuterHTML:bt,findVmFromFrag:wt,mergeOptions:Nt,resolveAsset:jt,checkComponentAttr:Ct,commonTagRE:Wn,reservedTagRE:Bn,warn:In}),Yn=0,Kn=new $(1e3),tr=0,er=1,ir=2,nr=3,rr=0,sr=1,or=2,ar=3,hr=4,lr=5,cr=6,ur=7,fr=8,pr=[];pr[rr]={ws:[rr],ident:[ar,tr],"[":[hr],eof:[ur]},pr[sr]={ws:[sr],".":[or],"[":[hr],eof:[ur]},pr[or]={ws:[or],ident:[ar,tr]},pr[ar]={ident:[ar,tr],0:[ar,tr],number:[ar,tr],ws:[sr,er],".":[or,er],"[":[hr,er],eof:[ur,er]},pr[hr]={"'":[lr,tr],'"':[cr,tr],"[":[hr,ir],"]":[sr,nr],eof:fr,else:[hr,tr]},pr[lr]={"'":[hr,tr],eof:fr,else:[lr,tr]},pr[cr]={'"':[hr,tr],eof:fr,else:[cr,tr]};var dr=Object.freeze({parsePath:Wt,getPath:Bt,setPath:zt}),vr=new $(1e3),mr="Math,Date,this,true,false,null,undefined,Infinity,NaN,isNaN,isFinite,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,parseInt,parseFloat",gr=new RegExp("^("+mr.replace(/,/g,"\\b|")+"\\b)"),_r="break,case,class,catch,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,in,instanceof,let,return,super,switch,throw,try,var,while,with,yield,enum,await,implements,package,protected,static,interface,private,public",yr=new RegExp("^("+_r.replace(/,/g,"\\b|")+"\\b)"),br=/\s/g,wr=/\n/g,Cr=/[\{,]\s*[\w\$_]+\s*:|('(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*"|`(?:[^`\\]|\\.)*\$\{|\}(?:[^`\\"']|\\.)*`|`(?:[^`\\]|\\.)*`)|new |typeof |void /g,$r=/"(\d+)"/g,kr=/^[A-Za-z_$][\w$]*(?:\.[A-Za-z_$][\w$]*|\['.*?'\]|\[".*?"\]|\[\d+\]|\[[A-Za-z_$][\w$]*\])*$/,xr=/[^\w$\.](?:[A-Za-z_$][\w$]*)/g,Ar=/^(?:true|false|null|undefined|Infinity|NaN)$/,Or=[],Tr=Object.freeze({parseExpression:Yt,isSimplePath:Kt}),Nr=[],jr=[],Er={},Fr={},Sr=!1,Dr=0;re.prototype.get=function(){this.beforeGet();var t,e=this.scope||this.vm;try{t=this.getter.call(e,e)}catch(t){}return this.deep&&se(t),this.preProcess&&(t=this.preProcess(t)),this.filters&&(t=e._applyFilters(t,null,this.filters,!1)),this.postProcess&&(t=this.postProcess(t)),this.afterGet(),t},re.prototype.set=function(t){var e=this.scope||this.vm;this.filters&&(t=e._applyFilters(t,this.value,this.filters,!0));try{this.setter.call(e,e,t)}catch(t){}var i=e.$forContext;if(i&&i.alias===this.expression){if(i.filters)return;i._withLock(function(){e.$key?i.rawValue[e.$key]=t:i.rawValue.$set(e.$index,t)})}},re.prototype.beforeGet=function(){Et.target=this},re.prototype.addDep=function(t){var e=t.id;this.newDepIds.has(e)||(this.newDepIds.add(e),this.newDeps.push(t),this.depIds.has(e)||t.addSub(this))},re.prototype.afterGet=function(){Et.target=null;for(var t=this.deps.length;t--;){var e=this.deps[t];this.newDepIds.has(e.id)||e.removeSub(this)}var i=this.depIds;this.depIds=this.newDepIds,this.newDepIds=i,this.newDepIds.clear(),i=this.deps,this.deps=this.newDeps,this.newDeps=i,this.newDeps.length=0},re.prototype.update=function(t){this.lazy?this.dirty=!0:this.sync||!Hn.async?this.run():(this.shallow=this.queued?!!t&&this.shallow:!!t,this.queued=!0,ne(this))},re.prototype.run=function(){if(this.active){var t=this.get();if(t!==this.value||(m(t)||this.deep)&&!this.shallow){var e=this.value;this.value=t;this.prevError;this.cb.call(this.vm,t,e)}this.queued=this.shallow=!1}},re.prototype.evaluate=function(){var t=Et.target;this.value=this.get(),this.dirty=!1,Et.target=t},re.prototype.depend=function(){for(var t=this.deps.length;t--;)this.deps[t].depend()},re.prototype.teardown=function(){if(this.active){this.vm._isBeingDestroyed||this.vm._vForRemoving||this.vm._watchers.$remove(this);for(var t=this.deps.length;t--;)this.deps[t].removeSub(this);this.active=!1,this.vm=this.cb=this.value=null}};var Pr=new hn,Rr={bind:function(){this.attr=3===this.el.nodeType?"data":"textContent"},update:function(t){this.el[this.attr]=s(t)}},Lr=new $(1e3),Hr=new $(1e3),Ir={efault:[0,"",""],legend:[1,"<fieldset>","</fieldset>"],tr:[2,"<table><tbody>","</tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"]};Ir.td=Ir.th=[3,"<table><tbody><tr>","</tr></tbody></table>"],Ir.option=Ir.optgroup=[1,'<select multiple="multiple">',"</select>"],Ir.thead=Ir.tbody=Ir.colgroup=Ir.caption=Ir.tfoot=[1,"<table>","</table>"],Ir.g=Ir.defs=Ir.symbol=Ir.use=Ir.image=Ir.text=Ir.circle=Ir.ellipse=Ir.line=Ir.path=Ir.polygon=Ir.polyline=Ir.rect=[1,'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events"version="1.1">',"</svg>"];var Mr=/<([\w:-]+)/,Vr=/&#?\w+?;/,Wr=/<!--/,Br=function(){if(Qi){var t=document.createElement("div");return t.innerHTML="<template>1</template>",!t.cloneNode(!0).firstChild.innerHTML}return!1}(),zr=function(){if(Qi){var t=document.createElement("textarea");return t.placeholder="t","t"===t.cloneNode(!0).value}return!1}(),Ur=Object.freeze({cloneNode:le,parseTemplate:ce}),qr={bind:function(){8===this.el.nodeType&&(this.nodes=[],this.anchor=vt("v-html"),rt(this.el,this.anchor))},update:function(t){t=s(t),this.nodes?this.swap(t):this.el.innerHTML=t},swap:function(t){for(var e=this.nodes.length;e--;)it(this.nodes[e]);var i=ce(t,!0,!0);this.nodes=d(i.childNodes),tt(i,this.anchor)}};ue.prototype.callHook=function(t){var e,i;for(e=0,i=this.childFrags.length;e<i;e++)this.childFrags[e].callHook(t);for(e=0,i=this.children.length;e<i;e++)t(this.children[e])},ue.prototype.beforeRemove=function(){var t,e;for(t=0,e=this.childFrags.length;t<e;t++)this.childFrags[t].beforeRemove(!1);for(t=0,e=this.children.length;t<e;t++)this.children[t].$destroy(!1,!0);var i=this.unlink.dirs;for(t=0,e=i.length;t<e;t++)i[t]._watcher&&i[t]._watcher.teardown()},ue.prototype.destroy=function(){this.parentFrag&&this.parentFrag.childFrags.$remove(this),this.node.__v_frag=null,this.unlink()};var Jr=new $(5e3);_e.prototype.create=function(t,e,i){var n=le(this.template);return new ue(this.linker,this.vm,n,t,e,i)};var Qr=700,Gr=800,Zr=850,Xr=1100,Yr=1500,Kr=1500,ts=1750,es=2100,is=2200,ns=2300,rs=0,ss={priority:is,terminal:!0,params:["track-by","stagger","enter-stagger","leave-stagger"],bind:function(){var t=this.expression.match(/(.*) (?:in|of) (.*)/);if(t){var e=t[1].match(/\((.*),(.*)\)/);e?(this.iterator=e[1].trim(),this.alias=e[2].trim()):this.alias=t[1].trim(),this.expression=t[2]}if(this.alias){this.id="__v-for__"+ ++rs;var i=this.el.tagName;this.isOption=("OPTION"===i||"OPTGROUP"===i)&&"SELECT"===this.el.parentNode.tagName,this.start=vt("v-for-start"),this.end=vt("v-for-end"),rt(this.el,this.end),tt(this.start,this.end),this.cache=Object.create(null),this.factory=new _e(this.vm,this.el)}},update:function(t){this.diff(t),this.updateRef(),this.updateModel()},diff:function(t){var e,n,r,s,o,a,h=t[0],l=this.fromObject=m(h)&&i(h,"$key")&&i(h,"$value"),c=this.params.trackBy,u=this.frags,f=this.frags=new Array(t.length),p=this.alias,d=this.iterator,v=this.start,g=this.end,_=Z(v),y=!u;for(e=0,n=t.length;e<n;e++)h=t[e],s=l?h.$key:null,o=l?h.$value:h,a=!m(o),r=!y&&this.getCachedFrag(o,e,s),r?(r.reused=!0,r.scope.$index=e,s&&(r.scope.$key=s),d&&(r.scope[d]=null!==s?s:e),(c||l||a)&&Ft(function(){r.scope[p]=o})):(r=this.create(o,p,e,s),r.fresh=!y),f[e]=r,y&&r.before(g);if(!y){var b=0,w=u.length-f.length;for(this.vm._vForRemoving=!0,e=0,n=u.length;e<n;e++)r=u[e],r.reused||(this.deleteCachedFrag(r),this.remove(r,b++,w,_));this.vm._vForRemoving=!1,b&&(this.vm._watchers=this.vm._watchers.filter(function(t){return t.active}));var C,$,k,x=0;for(e=0,n=f.length;e<n;e++)r=f[e],C=f[e-1],$=C?C.staggerCb?C.staggerAnchor:C.end||C.node:v,r.reused&&!r.staggerCb?(k=ye(r,v,this.id),k===C||k&&ye(k,v,this.id)===C||this.move(r,$)):this.insert(r,x++,$,_),r.reused=r.fresh=!1}},create:function(t,e,i,n){var r=this._host,s=this._scope||this.vm,o=Object.create(s);o.$refs=Object.create(s.$refs),o.$els=Object.create(s.$els),o.$parent=s,o.$forContext=this,Ft(function(){Lt(o,e,t)}),Lt(o,"$index",i),n?Lt(o,"$key",n):o.$key&&_(o,"$key",null),this.iterator&&Lt(o,this.iterator,null!==n?n:i);var a=this.factory.create(r,o,this._frag);return a.forId=this.id,this.cacheFrag(t,a,i,n),a},updateRef:function(){var t=this.descriptor.ref;if(t){var e,i=(this._scope||this.vm).$refs;this.fromObject?(e={},this.frags.forEach(function(t){e[t.scope.$key]=wt(t)})):e=this.frags.map(wt),i[t]=e}},updateModel:function(){if(this.isOption){var t=this.start.parentNode,e=t&&t.__v_model;e&&e.forceUpdate()}},insert:function(t,e,i,n){t.staggerCb&&(t.staggerCb.cancel(),t.staggerCb=null);var r=this.getStagger(t,e,null,"enter");if(n&&r){var s=t.staggerAnchor;s||(s=t.staggerAnchor=vt("stagger-anchor"),s.__v_frag=t),et(s,i);var o=t.staggerCb=w(function(){t.staggerCb=null,t.before(s),it(s)});setTimeout(o,r)}else{var a=i.nextSibling;a||(et(this.end,i),a=this.end),t.before(a)}},remove:function(t,e,i,n){if(t.staggerCb)return t.staggerCb.cancel(),void(t.staggerCb=null);var r=this.getStagger(t,e,i,"leave");if(n&&r){var s=t.staggerCb=w(function(){t.staggerCb=null,t.remove()});setTimeout(s,r)}else t.remove()},move:function(t,e){e.nextSibling||this.end.parentNode.appendChild(this.end),t.before(e.nextSibling,!1)},cacheFrag:function(t,e,n,r){var s,o=this.params.trackBy,a=this.cache,h=!m(t);r||o||h?(s=we(n,r,t,o),a[s]||(a[s]=e)):(s=this.id,i(t,s)?null===t[s]&&(t[s]=e):Object.isExtensible(t)&&_(t,s,e)),e.raw=t},getCachedFrag:function(t,e,i){var n,r=this.params.trackBy,s=!m(t);if(i||r||s){var o=we(e,i,t,r);n=this.cache[o]}else n=t[this.id];return n&&(n.reused||n.fresh),n},deleteCachedFrag:function(t){var e=t.raw,n=this.params.trackBy,r=t.scope,s=r.$index,o=i(r,"$key")&&r.$key,a=!m(e);if(n||o||a){var h=we(s,o,e,n);this.cache[h]=null}else e[this.id]=null,t.raw=null},getStagger:function(t,e,i,n){n+="Stagger";var r=t.node.__v_trans,s=r&&r.hooks,o=s&&(s[n]||s.stagger);return o?o.call(t,e,i):e*parseInt(this.params[n]||this.params.stagger,10)},_preProcess:function(t){return this.rawValue=t,t},_postProcess:function(t){if(qi(t))return t;if(g(t)){for(var e,i=Object.keys(t),n=i.length,r=new Array(n);n--;)e=i[n],r[n]={$key:e,$value:t[e]};return r}return"number"!=typeof t||isNaN(t)||(t=be(t)),t||[]},unbind:function(){if(this.descriptor.ref&&((this._scope||this.vm).$refs[this.descriptor.ref]=null),this.frags)for(var t,e=this.frags.length;e--;)t=this.frags[e],this.deleteCachedFrag(t),t.destroy()}},os={priority:es,terminal:!0,bind:function(){var t=this.el;if(t.__vue__)this.invalid=!0;else{var e=t.nextElementSibling;e&&null!==X(e,"v-else")&&(it(e),this.elseEl=e),this.anchor=vt("v-if"),rt(t,this.anchor)}},update:function(t){this.invalid||(t?this.frag||(this.insert(),this.updateRef(t)):(this.updateRef(t),this.remove()))},insert:function(){this.elseFrag&&(this.elseFrag.remove(),this.elseFrag=null),this.factory||(this.factory=new _e(this.vm,this.el)),this.frag=this.factory.create(this._host,this._scope,this._frag),this.frag.before(this.anchor)},remove:function(){this.frag&&(this.frag.remove(),this.frag=null),this.elseEl&&!this.elseFrag&&(this.elseFactory||(this.elseFactory=new _e(this.elseEl._context||this.vm,this.elseEl)),this.elseFrag=this.elseFactory.create(this._host,this._scope,this._frag),this.elseFrag.before(this.anchor))},updateRef:function(t){var e=this.descriptor.ref;if(e){var i=(this.vm||this._scope).$refs,n=i[e],r=this._frag.scope.$key;n&&(t?Array.isArray(n)?n.push(wt(this._frag)):n[r]=wt(this._frag):Array.isArray(n)?n.$remove(wt(this._frag)):(n[r]=null,delete n[r]))}},unbind:function(){this.frag&&this.frag.destroy(),this.elseFrag&&this.elseFrag.destroy()}},as={bind:function(){var t=this.el.nextElementSibling;t&&null!==X(t,"v-else")&&(this.elseEl=t)},update:function(t){this.apply(this.el,t),this.elseEl&&this.apply(this.elseEl,!t)},apply:function(t,e){function i(){t.style.display=e?"":"none"}Z(t)?Q(t,e?1:-1,i,this.vm):i()}},hs={bind:function(){var t=this,e=this.el,i="range"===e.type,n=this.params.lazy,r=this.params.number,s=this.params.debounce,a=!1;if(Ki||i||(this.on("compositionstart",function(){a=!0}),this.on("compositionend",function(){a=!1,n||t.listener()})),this.focused=!1,i||n||(this.on("focus",function(){t.focused=!0}),this.on("blur",function(){t.focused=!1,t._frag&&!t._frag.inserted||t.rawListener()})),this.listener=this.rawListener=function(){if(!a&&t._bound){var n=r||i?o(e.value):e.value;t.set(n),an(function(){t._bound&&!t.focused&&t.update(t._watcher.value)})}},s&&(this.listener=y(this.listener,s)),this.hasjQuery="function"==typeof jQuery,this.hasjQuery){var h=jQuery.fn.on?"on":"bind";jQuery(e)[h]("change",this.rawListener),n||jQuery(e)[h]("input",this.listener)}else this.on("change",this.rawListener),n||this.on("input",this.listener);!n&&Yi&&(this.on("cut",function(){an(t.listener)}),this.on("keyup",function(e){46!==e.keyCode&&8!==e.keyCode||t.listener()})),(e.hasAttribute("value")||"TEXTAREA"===e.tagName&&e.value.trim())&&(this.afterBind=this.listener)},update:function(t){t=s(t),t!==this.el.value&&(this.el.value=t)},unbind:function(){var t=this.el;if(this.hasjQuery){var e=jQuery.fn.off?"off":"unbind";jQuery(t)[e]("change",this.listener),jQuery(t)[e]("input",this.listener)}}},ls={bind:function(){var t=this,e=this.el;this.getValue=function(){if(e.hasOwnProperty("_value"))return e._value;var i=e.value;return t.params.number&&(i=o(i)),i},this.listener=function(){t.set(t.getValue())},this.on("change",this.listener),e.hasAttribute("checked")&&(this.afterBind=this.listener)},update:function(t){this.el.checked=C(t,this.getValue())}},cs={bind:function(){var t=this,e=this,i=this.el;this.forceUpdate=function(){e._watcher&&e.update(e._watcher.get())};var n=this.multiple=i.hasAttribute("multiple");this.listener=function(){var t=Ce(i,n);t=e.params.number?qi(t)?t.map(o):o(t):t,e.set(t)},this.on("change",this.listener);var r=Ce(i,n,!0);(n&&r.length||!n&&null!==r)&&(this.afterBind=this.listener),this.vm.$on("hook:attached",function(){an(t.forceUpdate)}),Z(i)||an(this.forceUpdate)},update:function(t){var e=this.el;e.selectedIndex=-1;for(var i,n,r=this.multiple&&qi(t),s=e.options,o=s.length;o--;)i=s[o],n=i.hasOwnProperty("_value")?i._value:i.value,i.selected=r?$e(t,n)>-1:C(t,n)},unbind:function(){this.vm.$off("hook:attached",this.forceUpdate)}},us={bind:function(){function t(){var t=i.checked;return t&&i.hasOwnProperty("_trueValue")?i._trueValue:!t&&i.hasOwnProperty("_falseValue")?i._falseValue:t}var e=this,i=this.el;this.getValue=function(){return i.hasOwnProperty("_value")?i._value:e.params.number?o(i.value):i.value},this.listener=function(){var n=e._watcher.get();if(qi(n)){var r=e.getValue(),s=b(n,r);i.checked?s<0&&e.set(n.concat(r)):s>-1&&e.set(n.slice(0,s).concat(n.slice(s+1)))}else e.set(t())},this.on("change",this.listener),i.hasAttribute("checked")&&(this.afterBind=this.listener)},update:function(t){var e=this.el;qi(t)?e.checked=b(t,this.getValue())>-1:e.hasOwnProperty("_trueValue")?e.checked=C(t,e._trueValue):e.checked=!!t}},fs={text:hs,radio:ls,select:cs,checkbox:us},ps={priority:Gr,twoWay:!0,handlers:fs,params:["lazy","number","debounce"],bind:function(){this.checkFilters(),this.hasRead&&!this.hasWrite;var t,e=this.el,i=e.tagName;if("INPUT"===i)t=fs[e.type]||fs.text;else if("SELECT"===i)t=fs.select;else{if("TEXTAREA"!==i)return;t=fs.text}e.__v_model=this,t.bind.call(this),this.update=t.update,this._unbind=t.unbind},checkFilters:function(){var t=this.filters;if(t)for(var e=t.length;e--;){var i=jt(this.vm.$options,"filters",t[e].name);("function"==typeof i||i.read)&&(this.hasRead=!0),i.write&&(this.hasWrite=!0)}},unbind:function(){this.el.__v_model=null,this._unbind&&this._unbind()}},ds={esc:27,tab:9,enter:13,space:32,delete:[8,46],up:38,left:37,right:39,down:40},vs={priority:Qr,acceptStatement:!0,keyCodes:ds,bind:function(){if("IFRAME"===this.el.tagName&&"load"!==this.arg){var t=this;this.iframeBind=function(){st(t.el.contentWindow,t.arg,t.handler,t.modifiers.capture)},this.on("load",this.iframeBind)}},update:function(t){if(this.descriptor.raw||(t=function(){}),"function"==typeof t){this.modifiers.stop&&(t=xe(t)),this.modifiers.prevent&&(t=Ae(t)),this.modifiers.self&&(t=Oe(t));var e=Object.keys(this.modifiers).filter(function(t){return"stop"!==t&&"prevent"!==t&&"self"!==t&&"capture"!==t});e.length&&(t=ke(t,e)),this.reset(),this.handler=t,this.iframeBind?this.iframeBind():st(this.el,this.arg,this.handler,this.modifiers.capture)}},reset:function(){var t=this.iframeBind?this.el.contentWindow:this.el;this.handler&&ot(t,this.arg,this.handler)},unbind:function(){this.reset()}},ms=["-webkit-","-moz-","-ms-"],gs=["Webkit","Moz","ms"],_s=/!important;?$/,ys=Object.create(null),bs=null,ws={deep:!0,update:function(t){"string"==typeof t?this.el.style.cssText=t:qi(t)?this.handleObject(t.reduce(v,{})):this.handleObject(t||{})},handleObject:function(t){var e,i,n=this.cache||(this.cache={});for(e in n)e in t||(this.handleSingle(e,null),delete n[e]);
for(e in t)i=t[e],i!==n[e]&&(n[e]=i,this.handleSingle(e,i))},handleSingle:function(t,e){if(t=Te(t))if(null!=e&&(e+=""),e){var i=_s.test(e)?"important":"";i?(e=e.replace(_s,"").trim(),this.el.style.setProperty(t.kebab,e,i)):this.el.style[t.camel]=e}else this.el.style[t.camel]=""}},Cs="http://www.w3.org/1999/xlink",$s=/^xlink:/,ks=/^v-|^:|^@|^(?:is|transition|transition-mode|debounce|track-by|stagger|enter-stagger|leave-stagger)$/,xs=/^(?:value|checked|selected|muted)$/,As=/^(?:draggable|contenteditable|spellcheck)$/,Os={value:"_value","true-value":"_trueValue","false-value":"_falseValue"},Ts={priority:Zr,bind:function(){var t=this.arg,e=this.el.tagName;t||(this.deep=!0);var i=this.descriptor,n=i.interp;n&&(i.hasOneTime&&(this.expression=W(n,this._scope||this.vm)),(ks.test(t)||"name"===t&&("PARTIAL"===e||"SLOT"===e))&&(this.el.removeAttribute(t),this.invalid=!0))},update:function(t){if(!this.invalid){var e=this.arg;this.arg?this.handleSingle(e,t):this.handleObject(t||{})}},handleObject:ws.handleObject,handleSingle:function(t,e){var i=this.el,n=this.descriptor.interp;if(this.modifiers.camel&&(t=l(t)),!n&&xs.test(t)&&t in i){var r="value"===t&&null==e?"":e;i[t]!==r&&(i[t]=r)}var s=Os[t];if(!n&&s){i[s]=e;var o=i.__v_model;o&&o.listener()}return"value"===t&&"TEXTAREA"===i.tagName?void i.removeAttribute(t):void(As.test(t)?i.setAttribute(t,e?"true":"false"):null!=e&&e!==!1?"class"===t?(i.__v_trans&&(e+=" "+i.__v_trans.id+"-transition"),ht(i,e)):$s.test(t)?i.setAttributeNS(Cs,t,e===!0?"":e):i.setAttribute(t,e===!0?"":e):i.removeAttribute(t))}},Ns={priority:Yr,bind:function(){if(this.arg){var t=this.id=l(this.arg),e=(this._scope||this.vm).$els;i(e,t)?e[t]=this.el:Lt(e,t,this.el)}},unbind:function(){var t=(this._scope||this.vm).$els;t[this.id]===this.el&&(t[this.id]=null)}},js={bind:function(){}},Es={bind:function(){var t=this.el;this.vm.$once("pre-hook:compiled",function(){t.removeAttribute("v-cloak")})}},Fs={text:Rr,html:qr,for:ss,if:os,show:as,model:ps,on:vs,bind:Ts,el:Ns,ref:js,cloak:Es},Ss={deep:!0,update:function(t){t?"string"==typeof t?this.setClass(t.trim().split(/\s+/)):this.setClass(je(t)):this.cleanup()},setClass:function(t){this.cleanup(t);for(var e=0,i=t.length;e<i;e++){var n=t[e];n&&Ee(this.el,n,lt)}this.prevKeys=t},cleanup:function(t){var e=this.prevKeys;if(e)for(var i=e.length;i--;){var n=e[i];(!t||t.indexOf(n)<0)&&Ee(this.el,n,ct)}}},Ds={priority:Kr,params:["keep-alive","transition-mode","inline-template"],bind:function(){this.el.__vue__||(this.keepAlive=this.params.keepAlive,this.keepAlive&&(this.cache={}),this.params.inlineTemplate&&(this.inlineTemplate=ut(this.el,!0)),this.pendingComponentCb=this.Component=null,this.pendingRemovals=0,this.pendingRemovalCb=null,this.anchor=vt("v-component"),rt(this.el,this.anchor),this.el.removeAttribute("is"),this.el.removeAttribute(":is"),this.descriptor.ref&&this.el.removeAttribute("v-ref:"+u(this.descriptor.ref)),this.literal&&this.setComponent(this.expression))},update:function(t){this.literal||this.setComponent(t)},setComponent:function(t,e){if(this.invalidatePending(),t){var i=this;this.resolveComponent(t,function(){i.mountComponent(e)})}else this.unbuild(!0),this.remove(this.childVM,e),this.childVM=null},resolveComponent:function(t,e){var i=this;this.pendingComponentCb=w(function(n){i.ComponentName=n.options.name||("string"==typeof t?t:null),i.Component=n,e()}),this.vm._resolveComponent(t,this.pendingComponentCb)},mountComponent:function(t){this.unbuild(!0);var e=this,i=this.Component.options.activate,n=this.getCached(),r=this.build();i&&!n?(this.waitingFor=r,Fe(i,r,function(){e.waitingFor===r&&(e.waitingFor=null,e.transition(r,t))})):(n&&r._updateRef(),this.transition(r,t))},invalidatePending:function(){this.pendingComponentCb&&(this.pendingComponentCb.cancel(),this.pendingComponentCb=null)},build:function(t){var e=this.getCached();if(e)return e;if(this.Component){var i={name:this.ComponentName,el:le(this.el),template:this.inlineTemplate,parent:this._host||this.vm,_linkerCachable:!this.inlineTemplate,_ref:this.descriptor.ref,_asComponent:!0,_isRouterView:this._isRouterView,_context:this.vm,_scope:this._scope,_frag:this._frag};t&&v(i,t);var n=new this.Component(i);return this.keepAlive&&(this.cache[this.Component.cid]=n),n}},getCached:function(){return this.keepAlive&&this.cache[this.Component.cid]},unbuild:function(t){this.waitingFor&&(this.keepAlive||this.waitingFor.$destroy(),this.waitingFor=null);var e=this.childVM;return!e||this.keepAlive?void(e&&(e._inactive=!0,e._updateRef(!0))):void e.$destroy(!1,t)},remove:function(t,e){var i=this.keepAlive;if(t){this.pendingRemovals++,this.pendingRemovalCb=e;var n=this;t.$remove(function(){n.pendingRemovals--,i||t._cleanup(),!n.pendingRemovals&&n.pendingRemovalCb&&(n.pendingRemovalCb(),n.pendingRemovalCb=null)})}else e&&e()},transition:function(t,e){var i=this,n=this.childVM;switch(n&&(n._inactive=!0),t._inactive=!1,this.childVM=t,i.params.transitionMode){case"in-out":t.$before(i.anchor,function(){i.remove(n,e)});break;case"out-in":i.remove(n,function(){t.$before(i.anchor,e)});break;default:i.remove(n),t.$before(i.anchor,e)}},unbind:function(){if(this.invalidatePending(),this.unbuild(),this.cache){for(var t in this.cache)this.cache[t].$destroy();this.cache=null}}},Ps=Hn._propBindingModes,Rs={},Ls=/^[$_a-zA-Z]+[\w$]*$/,Hs=Hn._propBindingModes,Is={bind:function(){var t=this.vm,e=t._context,i=this.descriptor.prop,n=i.path,r=i.parentPath,s=i.mode===Hs.TWO_WAY,o=this.parentWatcher=new re(e,r,function(e){Le(t,i,e)},{twoWay:s,filters:i.filters,scope:this._scope});if(Re(t,i,o.value),s){var a=this;t.$once("pre-hook:created",function(){a.childWatcher=new re(t,n,function(t){o.set(t)},{sync:!0})})}},unbind:function(){this.parentWatcher.teardown(),this.childWatcher&&this.childWatcher.teardown()}},Ms=[],Vs=!1,Ws="transition",Bs="animation",zs=tn+"Duration",Us=nn+"Duration",qs=Qi&&window.requestAnimationFrame,Js=qs?function(t){qs(function(){qs(t)})}:function(t){setTimeout(t,50)},Qs=ze.prototype;Qs.enter=function(t,e){this.cancelPending(),this.callHook("beforeEnter"),this.cb=e,lt(this.el,this.enterClass),t(),this.entered=!1,this.callHookWithCb("enter"),this.entered||(this.cancel=this.hooks&&this.hooks.enterCancelled,We(this.enterNextTick))},Qs.enterNextTick=function(){var t=this;this.justEntered=!0,Js(function(){t.justEntered=!1});var e=this.enterDone,i=this.getCssTransitionType(this.enterClass);this.pendingJsCb?i===Ws&&ct(this.el,this.enterClass):i===Ws?(ct(this.el,this.enterClass),this.setupCssCb(en,e)):i===Bs?this.setupCssCb(rn,e):e()},Qs.enterDone=function(){this.entered=!0,this.cancel=this.pendingJsCb=null,ct(this.el,this.enterClass),this.callHook("afterEnter"),this.cb&&this.cb()},Qs.leave=function(t,e){this.cancelPending(),this.callHook("beforeLeave"),this.op=t,this.cb=e,lt(this.el,this.leaveClass),this.left=!1,this.callHookWithCb("leave"),this.left||(this.cancel=this.hooks&&this.hooks.leaveCancelled,this.op&&!this.pendingJsCb&&(this.justEntered?this.leaveDone():We(this.leaveNextTick)))},Qs.leaveNextTick=function(){var t=this.getCssTransitionType(this.leaveClass);if(t){var e=t===Ws?en:rn;this.setupCssCb(e,this.leaveDone)}else this.leaveDone()},Qs.leaveDone=function(){this.left=!0,this.cancel=this.pendingJsCb=null,this.op(),ct(this.el,this.leaveClass),this.callHook("afterLeave"),this.cb&&this.cb(),this.op=null},Qs.cancelPending=function(){this.op=this.cb=null;var t=!1;this.pendingCssCb&&(t=!0,ot(this.el,this.pendingCssEvent,this.pendingCssCb),this.pendingCssEvent=this.pendingCssCb=null),this.pendingJsCb&&(t=!0,this.pendingJsCb.cancel(),this.pendingJsCb=null),t&&(ct(this.el,this.enterClass),ct(this.el,this.leaveClass)),this.cancel&&(this.cancel.call(this.vm,this.el),this.cancel=null)},Qs.callHook=function(t){this.hooks&&this.hooks[t]&&this.hooks[t].call(this.vm,this.el)},Qs.callHookWithCb=function(t){var e=this.hooks&&this.hooks[t];e&&(e.length>1&&(this.pendingJsCb=w(this[t+"Done"])),e.call(this.vm,this.el,this.pendingJsCb))},Qs.getCssTransitionType=function(t){if(!(!en||document.hidden||this.hooks&&this.hooks.css===!1||Ue(this.el))){var e=this.type||this.typeCache[t];if(e)return e;var i=this.el.style,n=window.getComputedStyle(this.el),r=i[zs]||n[zs];if(r&&"0s"!==r)e=Ws;else{var s=i[Us]||n[Us];s&&"0s"!==s&&(e=Bs)}return e&&(this.typeCache[t]=e),e}},Qs.setupCssCb=function(t,e){this.pendingCssEvent=t;var i=this,n=this.el,r=this.pendingCssCb=function(s){s.target===n&&(ot(n,t,r),i.pendingCssEvent=i.pendingCssCb=null,!i.pendingJsCb&&e&&e())};st(n,t,r)};var Gs={priority:Xr,update:function(t,e){var i=this.el,n=jt(this.vm.$options,"transitions",t);t=t||"v",e=e||"v",i.__v_trans=new ze(i,t,n,this.vm),ct(i,e+"-transition"),lt(i,t+"-transition")}},Zs={style:ws,class:Ss,component:Ds,prop:Is,transition:Gs},Xs=/^v-bind:|^:/,Ys=/^v-on:|^@/,Ks=/^v-([^:]+)(?:$|:(.*)$)/,to=/\.[^\.]+/g,eo=/^(v-bind:|:)?transition$/,io=1e3,no=2e3;ci.terminal=!0;var ro=/[^\w\-:\.]/,so=Object.freeze({compile:qe,compileAndLinkProps:Xe,compileRoot:Ye,transclude:gi,resolveSlots:wi}),oo=/^v-on:|^@/;Ai.prototype._bind=function(){var t=this.name,e=this.descriptor;if(("cloak"!==t||this.vm._isCompiled)&&this.el&&this.el.removeAttribute){var i=e.attr||"v-"+t;this.el.removeAttribute(i)}var n=e.def;if("function"==typeof n?this.update=n:v(this,n),this._setupParams(),this.bind&&this.bind(),this._bound=!0,this.literal)this.update&&this.update(e.raw);else if((this.expression||this.modifiers)&&(this.update||this.twoWay)&&!this._checkStatement()){var r=this;this.update?this._update=function(t,e){r._locked||r.update(t,e)}:this._update=xi;var s=this._preProcess?p(this._preProcess,this):null,o=this._postProcess?p(this._postProcess,this):null,a=this._watcher=new re(this.vm,this.expression,this._update,{filters:this.filters,twoWay:this.twoWay,deep:this.deep,preProcess:s,postProcess:o,scope:this._scope});this.afterBind?this.afterBind():this.update&&this.update(a.value)}},Ai.prototype._setupParams=function(){if(this.params){var t=this.params;this.params=Object.create(null);for(var e,i,n,r=t.length;r--;)e=u(t[r]),n=l(e),i=Y(this.el,e),null!=i?this._setupParamWatcher(n,i):(i=X(this.el,e),null!=i&&(this.params[n]=""===i||i))}},Ai.prototype._setupParamWatcher=function(t,e){var i=this,n=!1,r=(this._scope||this.vm).$watch(e,function(e,r){if(i.params[t]=e,n){var s=i.paramWatchers&&i.paramWatchers[t];s&&s.call(i,e,r)}else n=!0},{immediate:!0,user:!1});(this._paramUnwatchFns||(this._paramUnwatchFns=[])).push(r)},Ai.prototype._checkStatement=function(){var t=this.expression;if(t&&this.acceptStatement&&!Kt(t)){var e=Yt(t).get,i=this._scope||this.vm,n=function(t){i.$event=t,e.call(i,i),i.$event=null};return this.filters&&(n=i._applyFilters(n,null,this.filters)),this.update(n),!0}},Ai.prototype.set=function(t){this.twoWay&&this._withLock(function(){this._watcher.set(t)})},Ai.prototype._withLock=function(t){var e=this;e._locked=!0,t.call(e),an(function(){e._locked=!1})},Ai.prototype.on=function(t,e,i){st(this.el,t,e,i),(this._listeners||(this._listeners=[])).push([t,e])},Ai.prototype._teardown=function(){if(this._bound){this._bound=!1,this.unbind&&this.unbind(),this._watcher&&this._watcher.teardown();var t,e=this._listeners;if(e)for(t=e.length;t--;)ot(this.el,e[t][0],e[t][1]);var i=this._paramUnwatchFns;if(i)for(t=i.length;t--;)i[t]();this.vm=this.el=this._watcher=this._listeners=null}};var ao=/[^|]\|[^|]/;Ht(Si),$i(Si),ki(Si),Oi(Si),Ti(Si),Ni(Si),ji(Si),Ei(Si),Fi(Si);var ho={priority:ns,params:["name"],bind:function(){var t=this.params.name||"default",e=this.vm._slotContents&&this.vm._slotContents[t];e&&e.hasChildNodes()?this.compile(e.cloneNode(!0),this.vm._context,this.vm):this.fallback()},compile:function(t,e,i){if(t&&e){if(this.el.hasChildNodes()&&1===t.childNodes.length&&1===t.childNodes[0].nodeType&&t.childNodes[0].hasAttribute("v-if")){var n=document.createElement("template");n.setAttribute("v-else",""),n.innerHTML=this.el.innerHTML,n._context=this.vm,t.appendChild(n)}var r=i?i._scope:this._scope;this.unlink=e.$compile(t,i,r,this._frag)}t?rt(this.el,t):it(this.el)},fallback:function(){this.compile(ut(this.el,!0),this.vm)},unbind:function(){this.unlink&&this.unlink()}},lo={priority:ts,params:["name"],paramWatchers:{name:function(t){os.remove.call(this),t&&this.insert(t)}},bind:function(){this.anchor=vt("v-partial"),rt(this.el,this.anchor),this.insert(this.params.name)},insert:function(t){var e=jt(this.vm.$options,"partials",t,!0);e&&(this.factory=new _e(this.vm,e),os.insert.call(this))},unbind:function(){this.frag&&this.frag.destroy()}},co={slot:ho,partial:lo},uo=ss._postProcess,fo=/(\d{3})(?=\d)/g,po={orderBy:Ri,filterBy:Pi,limitBy:Di,json:{read:function(t,e){return"string"==typeof t?t:JSON.stringify(t,null,arguments.length>1?e:2)},write:function(t){try{return JSON.parse(t)}catch(e){return t}}},capitalize:function(t){return t||0===t?(t=t.toString(),t.charAt(0).toUpperCase()+t.slice(1)):""},uppercase:function(t){return t||0===t?t.toString().toUpperCase():""},lowercase:function(t){return t||0===t?t.toString().toLowerCase():""},currency:function(t,e,i){if(t=parseFloat(t),!isFinite(t)||!t&&0!==t)return"";e=null!=e?e:"$",i=null!=i?i:2;var n=Math.abs(t).toFixed(i),r=i?n.slice(0,-1-i):n,s=r.length%3,o=s>0?r.slice(0,s)+(r.length>3?",":""):"",a=i?n.slice(-1-i):"",h=t<0?"-":"";return h+e+o+r.slice(s).replace(fo,"$1,")+a},pluralize:function(t){var e=d(arguments,1),i=e.length;if(i>1){var n=t%10-1;return n in e?e[n]:e[i-1]}return e[0]+(1===t?"":"s")},debounce:function(t,e){if(t)return e||(e=300),y(t,e)}};return Hi(Si),Si.version="1.0.27",setTimeout(function(){Hn.devtools&&Gi&&Gi.emit("init",Si)},0),Si});
//# sourceMappingURL=vue.min.js.map
/**
 * vue-resource v0.7.0
 * https://github.com/vuejs/vue-resource
 * Released under the MIT License.
 */

!function(t,e){"object"==typeof exports&&"object"==typeof module?module.exports=e():"function"==typeof define&&define.amd?define([],e):"object"==typeof exports?exports.VueResource=e():t.VueResource=e()}(this,function(){return function(t){function e(r){if(n[r])return n[r].exports;var o=n[r]={exports:{},id:r,loaded:!1};return t[r].call(o.exports,o,o.exports,e),o.loaded=!0,o.exports}var n={};return e.m=t,e.c=n,e.p="",e(0)}([function(t,e,n){function r(t){var e=n(1);e.config=t.config,e.warning=t.util.warn,e.nextTick=t.util.nextTick,t.url=n(2),t.http=n(8),t.resource=n(23),t.Promise=n(10),Object.defineProperties(t.prototype,{$url:{get:function(){return e.options(t.url,this,this.$options.url)}},$http:{get:function(){return e.options(t.http,this,this.$options.http)}},$resource:{get:function(){return t.resource.bind(this)}},$promise:{get:function(){return function(e){return new t.Promise(e,this)}.bind(this)}}})}window.Vue&&Vue.use(r),t.exports=r},function(t,e){function n(t,e,o){for(var i in e)o&&(r.isPlainObject(e[i])||r.isArray(e[i]))?(r.isPlainObject(e[i])&&!r.isPlainObject(t[i])&&(t[i]={}),r.isArray(e[i])&&!r.isArray(t[i])&&(t[i]=[]),n(t[i],e[i],o)):void 0!==e[i]&&(t[i]=e[i])}var r=e,o=[],i=window.console;r.warn=function(t){i&&r.warning&&(!r.config.silent||r.config.debug)&&i.warn("[VueResource warn]: "+t)},r.error=function(t){i&&i.error(t)},r.trim=function(t){return t.replace(/^\s*|\s*$/g,"")},r.toLower=function(t){return t?t.toLowerCase():""},r.isArray=Array.isArray,r.isString=function(t){return"string"==typeof t},r.isFunction=function(t){return"function"==typeof t},r.isObject=function(t){return null!==t&&"object"==typeof t},r.isPlainObject=function(t){return r.isObject(t)&&Object.getPrototypeOf(t)==Object.prototype},r.options=function(t,e,n){return n=n||{},r.isFunction(n)&&(n=n.call(e)),r.merge(t.bind({$vm:e,$options:n}),t,{$options:n})},r.each=function(t,e){var n,o;if("number"==typeof t.length)for(n=0;n<t.length;n++)e.call(t[n],t[n],n);else if(r.isObject(t))for(o in t)t.hasOwnProperty(o)&&e.call(t[o],t[o],o);return t},r.defaults=function(t,e){for(var n in e)void 0===t[n]&&(t[n]=e[n]);return t},r.extend=function(t){var e=o.slice.call(arguments,1);return e.forEach(function(e){n(t,e)}),t},r.merge=function(t){var e=o.slice.call(arguments,1);return e.forEach(function(e){n(t,e,!0)}),t}},function(t,e,n){function r(t,e){var n,i=t;return s.isString(t)&&(i={url:t,params:e}),i=s.merge({},r.options,this.$options,i),r.transforms.forEach(function(t){n=o(t,n,this.$vm)},this),n(i)}function o(t,e,n){return function(r){return t.call(n,r,e)}}function i(t,e,n){var r,o=s.isArray(e),a=s.isPlainObject(e);s.each(e,function(e,u){r=s.isObject(e)||s.isArray(e),n&&(u=n+"["+(a||r?u:"")+"]"),!n&&o?t.add(e.name,e.value):r?i(t,e,u):t.add(u,e)})}var s=n(1),a=document.documentMode,u=document.createElement("a");r.options={url:"",root:null,params:{}},r.transforms=[n(3),n(5),n(6),n(7)],r.params=function(t){var e=[],n=encodeURIComponent;return e.add=function(t,e){s.isFunction(e)&&(e=e()),null===e&&(e=""),this.push(n(t)+"="+n(e))},i(e,t),e.join("&").replace(/%20/g,"+")},r.parse=function(t){return a&&(u.href=t,t=u.href),u.href=t,{href:u.href,protocol:u.protocol?u.protocol.replace(/:$/,""):"",port:u.port,host:u.host,hostname:u.hostname,pathname:"/"===u.pathname.charAt(0)?u.pathname:"/"+u.pathname,search:u.search?u.search.replace(/^\?/,""):"",hash:u.hash?u.hash.replace(/^#/,""):""}},t.exports=s.url=r},function(t,e,n){var r=n(4);t.exports=function(t){var e=[],n=r.expand(t.url,t.params,e);return e.forEach(function(e){delete t.params[e]}),n}},function(t,e){e.expand=function(t,e,n){var r=this.parse(t),o=r.expand(e);return n&&n.push.apply(n,r.vars),o},e.parse=function(t){var n=["+","#",".","/",";","?","&"],r=[];return{vars:r,expand:function(o){return t.replace(/\{([^\{\}]+)\}|([^\{\}]+)/g,function(t,i,s){if(i){var a=null,u=[];if(-1!==n.indexOf(i.charAt(0))&&(a=i.charAt(0),i=i.substr(1)),i.split(/,/g).forEach(function(t){var n=/([^:\*]*)(?::(\d+)|(\*))?/.exec(t);u.push.apply(u,e.getValues(o,a,n[1],n[2]||n[3])),r.push(n[1])}),a&&"+"!==a){var c=",";return"?"===a?c="&":"#"!==a&&(c=a),(0!==u.length?a:"")+u.join(c)}return u.join(",")}return e.encodeReserved(s)})}}},e.getValues=function(t,e,n,r){var o=t[n],i=[];if(this.isDefined(o)&&""!==o)if("string"==typeof o||"number"==typeof o||"boolean"==typeof o)o=o.toString(),r&&"*"!==r&&(o=o.substring(0,parseInt(r,10))),i.push(this.encodeValue(e,o,this.isKeyOperator(e)?n:null));else if("*"===r)Array.isArray(o)?o.filter(this.isDefined).forEach(function(t){i.push(this.encodeValue(e,t,this.isKeyOperator(e)?n:null))},this):Object.keys(o).forEach(function(t){this.isDefined(o[t])&&i.push(this.encodeValue(e,o[t],t))},this);else{var s=[];Array.isArray(o)?o.filter(this.isDefined).forEach(function(t){s.push(this.encodeValue(e,t))},this):Object.keys(o).forEach(function(t){this.isDefined(o[t])&&(s.push(encodeURIComponent(t)),s.push(this.encodeValue(e,o[t].toString())))},this),this.isKeyOperator(e)?i.push(encodeURIComponent(n)+"="+s.join(",")):0!==s.length&&i.push(s.join(","))}else";"===e?i.push(encodeURIComponent(n)):""!==o||"&"!==e&&"?"!==e?""===o&&i.push(""):i.push(encodeURIComponent(n)+"=");return i},e.isDefined=function(t){return void 0!==t&&null!==t},e.isKeyOperator=function(t){return";"===t||"&"===t||"?"===t},e.encodeValue=function(t,e,n){return e="+"===t||"#"===t?this.encodeReserved(e):encodeURIComponent(e),n?encodeURIComponent(n)+"="+e:e},e.encodeReserved=function(t){return t.split(/(%[0-9A-Fa-f]{2})/g).map(function(t){return/%[0-9A-Fa-f]/.test(t)||(t=encodeURI(t)),t}).join("")}},function(t,e,n){function r(t){return o(t,!0).replace(/%26/gi,"&").replace(/%3D/gi,"=").replace(/%2B/gi,"+")}function o(t,e){return encodeURIComponent(t).replace(/%40/gi,"@").replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,e?"%20":"+")}var i=n(1);t.exports=function(t,e){var n=[],o=e(t);return o=o.replace(/(\/?):([a-z]\w*)/gi,function(e,o,s){return i.warn("The `:"+s+"` parameter syntax has been deprecated. Use the `{"+s+"}` syntax instead."),t.params[s]?(n.push(s),o+r(t.params[s])):""}),n.forEach(function(e){delete t.params[e]}),o}},function(t,e,n){var r=n(1);t.exports=function(t,e){var n=Object.keys(r.url.options.params),o={},i=e(t);return r.each(t.params,function(t,e){-1===n.indexOf(e)&&(o[e]=t)}),o=r.url.params(o),o&&(i+=(-1==i.indexOf("?")?"?":"&")+o),i}},function(t,e,n){var r=n(1);t.exports=function(t,e){var n=e(t);return r.isString(t.root)&&!n.match(/^(https?:)?\//)&&(n=t.root+"/"+n),n}},function(t,e,n){function r(t,e){var n,u,c=i;return r.interceptors.forEach(function(t){c=a(t,this.$vm)(c)},this),e=o.isObject(t)?t:o.extend({url:t},e),n=o.merge({},r.options,this.$options,e),u=c(n).bind(this.$vm).then(function(t){return t.ok?t:s.reject(t)},function(t){return t instanceof Error&&o.error(t),s.reject(t)}),n.success&&u.success(n.success),n.error&&u.error(n.error),u}var o=n(1),i=n(9),s=n(10),a=n(13),u={"Content-Type":"application/json"};r.options={method:"get",data:"",params:{},headers:{},xhr:null,upload:null,jsonp:"callback",beforeSend:null,crossOrigin:null,emulateHTTP:!1,emulateJSON:!1,timeout:0},r.interceptors=[n(14),n(15),n(16),n(18),n(19),n(20),n(21)],r.headers={put:u,post:u,patch:u,"delete":u,common:{Accept:"application/json, text/plain, */*"},custom:{"X-Requested-With":"XMLHttpRequest"}},["get","put","post","patch","delete","jsonp"].forEach(function(t){r[t]=function(e,n,r,i){return o.isFunction(n)&&(i=r,r=n,n=void 0),o.isObject(r)&&(i=r,r=void 0),this(e,o.extend({method:t,data:n,success:r},i))}}),t.exports=o.http=r},function(t,e,n){function r(t){var e,n,r,i={};return o.isString(t)&&o.each(t.split("\n"),function(t){r=t.indexOf(":"),n=o.trim(o.toLower(t.slice(0,r))),e=o.trim(t.slice(r+1)),i[n]?o.isArray(i[n])?i[n].push(e):i[n]=[i[n],e]:i[n]=e}),i}var o=n(1),i=n(10),s=n(12);t.exports=function(t){var e=(t.client||s)(t);return i.resolve(e).then(function(t){if(t.headers){var e=r(t.headers);t.headers=function(t){return t?e[o.toLower(t)]:e}}return t.ok=t.status>=200&&t.status<300,t})}},function(t,e,n){function r(t,e){t instanceof i?this.promise=t:this.promise=new i(t.bind(e)),this.context=e}var o=n(1),i=window.Promise||n(11);r.all=function(t,e){return new r(i.all(t),e)},r.resolve=function(t,e){return new r(i.resolve(t),e)},r.reject=function(t,e){return new r(i.reject(t),e)},r.race=function(t,e){return new r(i.race(t),e)};var s=r.prototype;s.bind=function(t){return this.context=t,this},s.then=function(t,e){return t&&t.bind&&this.context&&(t=t.bind(this.context)),e&&e.bind&&this.context&&(e=e.bind(this.context)),this.promise=this.promise.then(t,e),this},s["catch"]=function(t){return t&&t.bind&&this.context&&(t=t.bind(this.context)),this.promise=this.promise["catch"](t),this},s["finally"]=function(t){return this.then(function(e){return t.call(this),e},function(e){return t.call(this),i.reject(e)})},s.success=function(t){return o.warn("The `success` method has been deprecated. Use the `then` method instead."),this.then(function(e){return t.call(this,e.data,e.status,e)||e})},s.error=function(t){return o.warn("The `error` method has been deprecated. Use the `catch` method instead."),this["catch"](function(e){return t.call(this,e.data,e.status,e)||e})},s.always=function(t){o.warn("The `always` method has been deprecated. Use the `finally` method instead.");var e=function(e){return t.call(this,e.data,e.status,e)||e};return this.then(e,e)},t.exports=r},function(t,e,n){function r(t){this.state=a,this.value=void 0,this.deferred=[];var e=this;try{t(function(t){e.resolve(t)},function(t){e.reject(t)})}catch(n){e.reject(n)}}var o=n(1),i=0,s=1,a=2;r.reject=function(t){return new r(function(e,n){n(t)})},r.resolve=function(t){return new r(function(e,n){e(t)})},r.all=function(t){return new r(function(e,n){function o(n){return function(r){s[n]=r,i+=1,i===t.length&&e(s)}}var i=0,s=[];0===t.length&&e(s);for(var a=0;a<t.length;a+=1)r.resolve(t[a]).then(o(a),n)})},r.race=function(t){return new r(function(e,n){for(var o=0;o<t.length;o+=1)r.resolve(t[o]).then(e,n)})};var u=r.prototype;u.resolve=function(t){var e=this;if(e.state===a){if(t===e)throw new TypeError("Promise settled with itself.");var n=!1;try{var r=t&&t.then;if(null!==t&&"object"==typeof t&&"function"==typeof r)return void r.call(t,function(t){n||e.resolve(t),n=!0},function(t){n||e.reject(t),n=!0})}catch(o){return void(n||e.reject(o))}e.state=i,e.value=t,e.notify()}},u.reject=function(t){var e=this;if(e.state===a){if(t===e)throw new TypeError("Promise settled with itself.");e.state=s,e.value=t,e.notify()}},u.notify=function(){var t=this;o.nextTick(function(){if(t.state!==a)for(;t.deferred.length;){var e=t.deferred.shift(),n=e[0],r=e[1],o=e[2],u=e[3];try{t.state===i?o("function"==typeof n?n.call(void 0,t.value):t.value):t.state===s&&("function"==typeof r?o(r.call(void 0,t.value)):u(t.value))}catch(c){u(c)}}})},u.then=function(t,e){var n=this;return new r(function(r,o){n.deferred.push([t,e,r,o]),n.notify()})},u["catch"]=function(t){return this.then(void 0,t)},t.exports=r},function(t,e,n){var r=n(1),o=n(10);t.exports=function(t){return new o(function(e){var n,o=new XMLHttpRequest,i={request:t};t.cancel=function(){o.abort()},o.open(t.method,r.url(t),!0),n=function(t){i.data=o.responseText,i.status=o.status,i.statusText=o.statusText,i.headers=o.getAllResponseHeaders(),e(i)},o.timeout=0,o.onload=n,o.onabort=n,o.onerror=n,o.ontimeout=function(){},o.onprogress=function(){},r.isPlainObject(t.xhr)&&r.extend(o,t.xhr),r.isPlainObject(t.upload)&&r.extend(o.upload,t.upload),r.each(t.headers||{},function(t,e){o.setRequestHeader(e,t)}),o.send(t.data)})}},function(t,e,n){function r(t,e,n){var r=i.resolve(t);return arguments.length<2?r:r.then(e,n)}var o=n(1),i=n(10);t.exports=function(t,e){return function(n){return o.isFunction(t)&&(t=t.call(e,i)),function(i){return o.isFunction(t.request)&&(i=t.request.call(e,i)),r(i,function(i){return r(n(i),function(n){return o.isFunction(t.response)&&(n=t.response.call(e,n)),n})})}}}},function(t,e,n){var r=n(1);t.exports={request:function(t){return r.isFunction(t.beforeSend)&&t.beforeSend.call(this,t),t}}},function(t,e){t.exports=function(){var t;return{request:function(e){return e.timeout&&(t=setTimeout(function(){e.cancel()},e.timeout)),e},response:function(e){return clearTimeout(t),e}}}},function(t,e,n){var r=n(17);t.exports={request:function(t){return"JSONP"==t.method&&(t.client=r),t}}},function(t,e,n){var r=n(1),o=n(10);t.exports=function(t){return new o(function(e){var n,o,i="_jsonp"+Math.random().toString(36).substr(2),s={request:t,data:null};t.params[t.jsonp]=i,t.cancel=function(){n({type:"cancel"})},o=document.createElement("script"),o.src=r.url(t),o.type="text/javascript",o.async=!0,window[i]=function(t){s.data=t},n=function(t){"load"===t.type&&null!==s.data?s.status=200:"error"===t.type?s.status=404:s.status=0,e(s),delete window[i],document.body.removeChild(o)},o.onload=n,o.onerror=n,document.body.appendChild(o)})}},function(t,e){t.exports={request:function(t){return t.emulateHTTP&&/^(PUT|PATCH|DELETE)$/i.test(t.method)&&(t.headers["X-HTTP-Method-Override"]=t.method,t.method="POST"),t}}},function(t,e,n){var r=n(1);t.exports={request:function(t){return t.emulateJSON&&r.isPlainObject(t.data)&&(t.headers["Content-Type"]="application/x-www-form-urlencoded",t.data=r.url.params(t.data)),r.isObject(t.data)&&/FormData/i.test(t.data.toString())&&delete t.headers["Content-Type"],r.isPlainObject(t.data)&&(t.data=JSON.stringify(t.data)),t},response:function(t){try{t.data=JSON.parse(t.data)}catch(e){}return t}}},function(t,e,n){var r=n(1);t.exports={request:function(t){return t.method=t.method.toUpperCase(),t.headers=r.extend({},r.http.headers.common,t.crossOrigin?{}:r.http.headers.custom,r.http.headers[t.method.toLowerCase()],t.headers),r.isPlainObject(t.data)&&/^(GET|JSONP)$/i.test(t.method)&&(r.extend(t.params,t.data),delete t.data),t}}},function(t,e,n){function r(t){var e=o.url.parse(o.url(t));return e.protocol!==a.protocol||e.host!==a.host}var o=n(1),i=n(22),s="withCredentials"in new XMLHttpRequest,a=o.url.parse(location.href);t.exports={request:function(t){return null===t.crossOrigin&&(t.crossOrigin=r(t)),t.crossOrigin&&(s||(t.client=i),t.emulateHTTP=!1),t}}},function(t,e,n){var r=n(1),o=n(10);t.exports=function(t){return new o(function(e){var n,o=new XDomainRequest,i={request:t};t.cancel=function(){o.abort()},o.open(t.method,r.url(t),!0),n=function(t){i.data=o.responseText,i.status=o.status,i.statusText=o.statusText,e(i)},o.timeout=0,o.onload=n,o.onabort=n,o.onerror=n,o.ontimeout=function(){},o.onprogress=function(){},o.send(t.data)})}},function(t,e,n){function r(t,e,n,s){var a=this,u={};return n=i.extend({},r.actions,n),i.each(n,function(n,r){n=i.merge({url:t,params:e||{}},s,n),u[r]=function(){return(a.$http||i.http)(o(n,arguments))}}),u}function o(t,e){var n,r,o,s=i.extend({},t),a={};switch(e.length){case 4:o=e[3],r=e[2];case 3:case 2:if(!i.isFunction(e[1])){a=e[0],n=e[1],r=e[2];break}if(i.isFunction(e[0])){r=e[0],o=e[1];break}r=e[1],o=e[2];case 1:i.isFunction(e[0])?r=e[0]:/^(POST|PUT|PATCH)$/i.test(s.method)?n=e[0]:a=e[0];break;case 0:break;default:throw"Expected up to 4 arguments [params, data, success, error], got "+e.length+" arguments"}return s.data=n,s.params=i.extend({},s.params,a),r&&(s.success=r),o&&(s.error=o),s}var i=n(1);r.actions={get:{method:"GET"},save:{method:"POST"},query:{method:"GET"},update:{method:"PUT"},remove:{method:"DELETE"},"delete":{method:"DELETE"}},t.exports=i.resource=r}])});
/************************************************
 DOCUMENT READY
 ************************************************/
var tabela = null;
var anketa_id = 0;

$(document).ready(function () {
    // pridobimo ID ankete, ko je dokument naložen
    anketa_id = $('#srv_meta_anketa_id').val();

    //vklopljeno iskanje za vse select box elemente
    $('.h-selected select.hierarhija-select').chosen();
    $('.h-selected.hierarhija-select').chosen();

    //Vklopi nice input file
    $("input[type=file]").nicefileinput({
        label: 'Poišči datoteko...'
    });


    //Data Tables konfiguracija za vpis šifrantov
    if ($('#vpis-sifrantov-admin-tabela').length > 0) {
        tabela = $('#vpis-sifrantov-admin-tabela').DataTable({
            "processing": true,
            "lengthMenu": [[20, 40, 100, 200, -1], [20, 40, 100, 200, "vse"]],
            "ajax": 'ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=get-datatables-data',
            "drawCallback": function (settings) {
                if (tabela.page.info().recordsTotal == 0) {
                    $('#div-datatables').hide();
                    $('#hierarhija-jstree-ime').hide();
                    $('#admin_hierarhija_jstree').hide();
                } else {
                    $('#div-datatables').show();
                    $('#hierarhija-jstree-ime').show();
                    $('#admin_hierarhija_jstree').show();
                }
            },
            "createdRow": function (row, data, rowIndex) {
                // gremo po vseh td elementih
                $.each($('td', row), function (colIndex) {

                    // SQL query nam vrne objek, ki ga uporabimo za datatables vpis vrstice
                    if (data[colIndex] && data[colIndex].label) {
                        // Vsaka celica ima id strukture, ki je potreben za urejanje uporabbnikov za posamezno vrstico
                        $(this).attr('data-struktura', data[colIndex].id);

                        // Vsaka celica ima številko nivoja - level
                        $(this).attr('data-level', data[colIndex].level);

                        // Prikaz podatkov
                        $(this).html(data[colIndex].label);
                    }

                });
            },
            "language": {
                "url": "modules/mod_hierarhija/js/vendor/datatables-slovenian.json"
            }
        });


    }


    // datatables za prikaz že vpisanih šifrantov
    if ($('#pregled-sifrantov-admin-tabela').length > 0) {
        $('#pregled-sifrantov-admin-tabela').DataTable({
            ajax: 'ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=get-datatables-data&m=1&p=1',
            "language": {
                "url": "modules/mod_hierarhija/js/vendor/datatables-slovenian.json"
            }
        });
    }


    // Klik na ikono za komentar
    $('.surveycomment').on('click', function () {
        dodajKomentar();
    });

    // Klik na ikono za upload logo - naloži logotip
    $('.logo-upload').on('click', function () {
        uploadLogo();
    });

    // Skrivamo filtrov in vprašanj pri analizah
    $('.znak').on('click', function (e) {
        var razred = e.currentTarget.className;

        if (razred == 'znak minus') {
            $('#div_means_dropdowns').animate('slow').hide();
            $('.minus').hide();
            $('.plus').show();
        } else {
            $('#div_means_dropdowns').animate('slow').show();
            $('.plus').hide();
            $('.minus').show();
        }
    });

    // Skrijemo error, ki se je pojavil
    $('.error-display').delay(10000).fadeOut('slow');
});
// uredi vrstico
// function urediVrsticoHierarhije(id) {
//     var anketa_id = $('#anketa_id').val();
//     var el = $('.btn-urejanje-hierarhije[data-id="' + id + '"]').parent().siblings().last();
//     var text = el.html().split("  -  ");
//
//     // pridobi vse uporabnike, ki so dodani na trenutno hierarhijo
//     var opcije = [];
//     // $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-uporabniki", {
//     //     id: id
//     // }).success(function (data) {
//     //     if (data == 0)
//     //         return opcije;
//     //
//     //     // vse emaili dodamo med opcije in polje, ki ga kasneje združimo v string
//     //     $.each(JSON.parse(data), function (key, val) {
//     //         opcije.push('<option value="' + key + '" ' + val.selected + '>' + val.uporabnik + '</option>');
//     //     });
//     //
//     //     el.html('Izbira: <b>' + text[0] + '</b><br/>Uporabniki:<select id="select2-email-' + id + '" multiple>' + opcije.join("") + '</seclect>');
//     //     $('.btn-urejanje-hierarhije[data-id="' + id + '"]').text('Vpiši').attr('onclick', 'vpisiVrsticoHierarhije(' + id + ')');
//     //
//     //     $('#select2-email-' + id).select2();
//     // });
//
//
// }

var vrsticaAktivnoUrejanje = {
    html: '',
    id: 0,
    izbris: 0
};

function urediVrsticoHierarhije(id) {
    // V kolikor je ponovno kliknil na urejanje, potem samo vrnemo in na ponovno neurejanje
    if (vrsticaAktivnoUrejanje.id == id) {
        // Vpišemo stare podatke vrstice, brez urejanja
        $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').html(vrsticaAktivnoUrejanje.html);

        // Odstranimo razrede
        $('#vpis-sifrantov-admin-tabela .h-uporabnik').remove();
        $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').removeClass();

        // če je bil uporabnik izbrisan potem osvežimo celotno tabelo
        if (vrsticaAktivnoUrejanje.izbris == 1)
            tabela.ajax.reload(null, false);


        // Ponastavimo globalno spremenljivko
        return vrsticaAktivnoUrejanje = {
            html: '',
            id: 0,
            izbris: 0
        };
    }

    // V kolikor obstaja podatek cele vrstice od prej in je aktivni razred . aktivno-urejanje, potem vsebino prekopiramo
    if (vrsticaAktivnoUrejanje.html.length > 0 && $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').length > 0)
        $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').html(vrsticaAktivnoUrejanje.html);


    // Izbriše ikonice za urejanje uprabnikov in odstrani aktivni razred urejanja
    $('#vpis-sifrantov-admin-tabela .h-uporabnik').remove();
    $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').removeClass();


    // Vsi elementi, ki vsebujejo ID strukture
    var vrstica = $('[data-struktura="' + id + '"]').parent();
    var stolpci = vrstica.children('[data-struktura]');

    // Obarvamo ozadje vrstice
    vrstica.addClass('aktivno-urejanje');

    // Celotno vrstico shranimo globalno in tudi id
    vrsticaAktivnoUrejanje = {
        html: $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').html(),
        id: id
    }

    // Pridobimo vse TD celice in v vsaki dodamo ikono ter uporabnike za urejati
    stolpci.each(function (key, val) {
        var self = this;
        var html = $(this).html().split("<br>");
        var idStrukture = $(this).attr('data-struktura');
        var uporabnikiHtml = '';

        // Ajax request, ki pridobi vse uporabnike za vsak nivo posebej
        $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-uporabniki', {
            id: idStrukture
        }, function (data) {
            var urejanjeUporabnika = '';

            // Ikona za pop up, kjer dodajamo še email
            urejanjeUporabnika = '<div class="h-uporabnik"><span class="faicon users icon-as_link" onclick="odpriPopup(' + idStrukture + ')"></span></div>';

            // Če imamo uporabnike na tem nivoju potem jih ustrezno dodamo
            if (data.length > 0) {
                var podatki = JSON.parse(data);

                // V kolikor imamo uporabnika samo na zadnjem nivoju, potem ni možnosti urejanja, ker ima opcijo briši nivo in uporabnika
                if ($(self).attr('data-level') == podatki.maxLevel) {

                    // Opcije za urejanje uporabnika ne potrebujemo na zadnjem nivoju
                    urejanjeUporabnika = '<div class="h-uporabnik"><span class="icon user-red" onclick="odpriPopup(' + idStrukture + ', 1)"></span></div>';

                    uporabnikiHtml = '<div class="h-uporabnik-prikazi">Uporabnik/i:' +
                        '<ul>';

                    // Dodamo vse uporabnike, ki so na tem nivoju
                    if (podatki.uporabniki) {
                        $.each(podatki.uporabniki, function (key, val) {
                            uporabnikiHtml += '<li>' + val.uporabnik + '</li>';
                        });
                    }

                    uporabnikiHtml += '</ul></div>';

                }
                else {
                    // Izpišemo uporabnike in možnost brisanja
                    uporabnikiHtml = '<div class="h-uporabnik-prikazi">Uporabnik/i:' +
                        '<ul>';

                    // Dodamo vse uporabnike, ki so na tem nivoju
                    if (podatki.uporabniki) {
                        $.each(podatki.uporabniki, function (key, val) {
                            uporabnikiHtml += '<li>' + val.uporabnik + ' <span class="icon brisi-x" data-id="' + val.id + '" onclick="izbrisiUporabnikaDataTables(' + val.id + ')"></span></li>';
                        });
                    }

                    uporabnikiHtml += '</ul></div>';
                }

            }

            $(self).html(html[0] + urejanjeUporabnika + uporabnikiHtml);

        });


    });

}

/**
 * Prikaži pop-up za uvoz vseh uporabnikov preko tekstovnega polja
 */
function uvoziUporabnike() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=import-user&s=get');
}

function shraniVseVpisaneUporabnike() {
    var users = $('#users-email-import').val();

    if (users.length < 5)
        return false;

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=import-user&s=post', {
        users: JSON.stringify(users)
    }).then(function (response) {
        // Pridobimoo uporabnike za select box
        var uporabniki = JSON.parse(response);

        gradnjaHierarhijeApp.user.dropdown = uporabniki;
        gradnjaHierarhijeApp.osebe.prikazi = false;

        // Zapremo Pop up
        vrednost_cancel();
    });
}

/**
 * Vrstico hierarhije kopiramo v možnost za urejanje uporabnikov, pridobimo zadnji id
 */
function kopirajVrsticoHierarhije(id) {
    // Poženemo funkcijo v datoteki custom-vue.js
    gradnjaHierarhijeApp.pridobiIdSifrantovInUporabnike(id);
}

// Odpre PopUp in naloži možnost za dodajanje novega uporabnika - textarea
function odpriPopup(id, last) {
    var last = last || 0;

    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=uredi-uporabnike-v-strukturi', {
        struktura: id,
        last: last
    });
}

/**
 * Zamenjamo email uporabnika na zadnjem nivoju z novim emailom - find and replace all
 */
function zamenjajUporabnikaZNovim() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=zamenjaj-uporabnika-v-strukturi');
}

/**
 * Testno preverimo koliko uporabnikov se bo zamenjalo
 */
function testnoPreveriKolikoUporabnikovBoZamnjenihVStrukturi() {
    var findEmail = $('#find-email').val();
    var replaceEmail = $('#replace-email').val();


    if (errorPreverjanjeEmailaZaZamenjavo(findEmail, replaceEmail))
        return false;

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-st-zamenjav', {
        'find_email': findEmail
    }).then(function (response) {
        var besedilo = 'Elektronski naslov <b>' + findEmail + '</b> ni bil najden med obstoječimi uporabniki in ga ni mogoče zamenjati.';

        if (response > 0)
            besedilo = 'Elektronski naslov <b>' + findEmail + '</b> bo zamenjan z naslovom <b>' + replaceEmail + '</b>.<br />Število zamenjav: <b>' + response + '</b>.';

        $('#st_zamenjav_uporabnikov').html(besedilo)
    });
}

function potriZamenjavoUporabnika() {
    var findEmail = $('#find-email').val();
    var replaceEmail = $('#replace-email').val();

    if (errorPreverjanjeEmailaZaZamenjavo(findEmail, replaceEmail))
        return false;

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-zamenjaj-uporabnika-z-novim', {
        'find_email': findEmail,
        'replace_email': replaceEmail
    }).then(function (response) {

        tabela.ajax.reload(null, false);

        // zapremo popup
        $('#fade').fadeOut('slow');
        $('#vrednost_edit').hide();
    });
}

/**
 * Preverimo, če sta emaila pravilno vpisana tist, ki ga iščemo in tisti, ki je za zamenjavo
 * @param findEmail
 * @param replaceEmail
 * @returns {boolean}
 */
function errorPreverjanjeEmailaZaZamenjavo(findEmail, replaceEmail) {
    // Preden preverimo odstranimo vse errorje
    $('#find-email').siblings('.error-label').hide();
    $('#find-email').removeClass('error');
    $('#replace-email').siblings('.error-label').hide();
    $('#replace-email').removeClass('error')

    if (!preveriFormatEmail(findEmail)) {
        $('#find-email').siblings('.error-label').show();
        $('#find-email').addClass('error');

        return true;
    }

    if (!preveriFormatEmail(replaceEmail)) {
        $('#replace-email').siblings('.error-label').show();
        $('#replace-email').addClass('error');

        return true;
    }

    return false;
};


function preveriFormatEmail(email) {
    var EMAIL_REGEX = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    return EMAIL_REGEX.test(email);
}

// Shranimo email vpisanih oseb pri urejanju za specifično strukturo
function shrani_email(id, last) {
    var emails = $('#vpis-email-popup').val().split('\n');
    var last = last || 0;

    //Loop po vseh vpisanih emailih
    $.each(emails, function (key, val) {
        val = val.split(',');

        // V kolikor email ni pravilen ga odstranimo iz polja
        if (!preveriPravilnoVpisanmail(val[0])) {
            emails.splice(key, 1);
        } else {
            emails[key] = val;
        }
    });

    // V kolikor ni bil vpisan email, ampak je samo klik na potrdi
    if (typeof emails[0] == 'undefined')
        return 'error';

    // Posredujemo samo pravilne emaile
    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-dodatne-uporabnike-k-strukturi', {
        uporabniki: JSON.stringify(emails),
        struktura: id,
        last: last
    }, function () {

        // osvežimo celoten DataTables
        tabela.ajax.reload(null, false);

        // Osvežimo tudi jsTree
        jstree_json_data(anketa_id, 1);

        // zapremo popup
        $('#fade').fadeOut('slow');
        $('#vrednost_edit').hide();

        // celotno strukturo shranimo v string in srv_hierarhija_save
        gradnjaHierarhijeApp.shraniUporabnikeNaHierarhijo();
    });

}

function preveriPravilnoVpisanmail(email) {
    var EMAIL_REGEX = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    return EMAIL_REGEX.test(email);
}

// Izbriši uporabnika iz DataTables
function izbrisiUporabnikaDataTables(id) {
    var str_id = $('[data-id="' + id + '"]').parents('[data-struktura]').attr('data-struktura');

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=brisi&m=uporabnika', {
        uporabnik_id: id,
        struktura_id: str_id
    }).then(function () {
        // Če je uporabnik uspešno izbrisan iz baze, potem tudi izbrišemo iz DataTables
        $('[data-id="' + id + '"]').parent().remove();

        vrsticaAktivnoUrejanje.izbris = 1;
    });
}

// vpiši vrstico v bazo
function vpisiVrsticoHierarhije(id) {
    var polje = [];

    // vse izbrani ID oseb
    $('#select2-email-' + id + ' option:selected').each(function () {
        polje.push($(this).val());
    });

    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-uporabniki", {
        uporabniki: JSON.stringify(polje),
        struktura: id
    }, function (data) {
        // v kolikor ni vpisanega uporabnika potem opozorimo
        if (data == 'uporabnik') {
            return swal({
                title: "Opozorilo!",
                text: "Uporabnik mora biti določen.",
                type: "error",
                timer: 2000,
                confirmButtonText: "OK"
            });
        }

        // osvežimo tabelo, ko smo vpisali podatke
        tabela.ajax.reload(null, false);
        jstree_json_data(anketa_id, 1);
    });

}

// datatables urejanje, brisanje
function brisiVrsticoHierarhije(id, osveziTabelo) {

    var osveziTabelo = osveziTabelo || 0;

    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=brisi_element_v_hierarhiji", {
        id: id
    }, function (p) {
        //Hierarhije je že zgrajena
        if (p == 2) {
            swal({
                title: "Hierarhija je zaklenjena!",
                text: "Brisanje ni več mogoče, ker je hierarhija zaklenjena za urejanje.",
                type: "error",
                timer: 2000,
                confirmButtonText: "OK"
            });
            //ko javimo napako moramo jstree osvežiti, ker v nasprotnem primeru js še vedno izbriše omenjen element
            jstree_json_data(anketa_id, 1);
        }

        //Hierarhije je že zgrajena
        if (p == 'obstaja') {
            swal({
                title: "Brisanje ni mogoče!",
                text: "Ne morete izbrisati omenjenega elementa, ker imate pod njem še izbrano hierarhijo.",
                type: "error",
                timer: 2000,
                confirmButtonText: "OK"
            });
            //ko javimo napako moramo jstree osvežiti, ker v nasprotnem primeru js še vedno izbriše omenjen element
            jstree_json_data(anketa_id, 1);
        }


        tabela.ajax.reload(null, false);
        jstree_json_data(anketa_id, 1);

        // celotno strukturo shranimo v string in srv_hierarhija_save
        gradnjaHierarhijeApp.shraniUporabnikeNaHierarhijo();
    });
}


//preverimo, če je obkljukano polje
function preveriCheckDodajEmail() {
    if ($("#dovoli-vpis-emaila").is(':checked')) {
        $('#vpis-emaila').show();
    }
    else {
        $('#vpis-emaila').val('').hide();
    }
}

/**
 * Opoyorimo v kolikor želi uporabni nadaljevati in ni shraniv emaila trenutnega uporabnika
 */
function opozoriUporabnikaKerNiPotrdilPodatkov(href) {
    var level = gradnjaHierarhijeApp.podatki.maxLevel;

    // V kolikor imamo uporabnika na zadnjem nivoju
    if (typeof gradnjaHierarhijeApp.osebe.nove[level] === 'object') {
        swal({
            title: "Opozorilo!",
            text: "Vnesli ste strukturo za dotičnega uporabnika, vendar omenjene podatke niste shranili. Ali jih želite izbrisati?",
            type: "error",
            showCancelButton: true,
            confirmButtonText: "Nadaljuj",
            cancelButtonText: "Prekliči"
        }, function (dismiss) {

            // V kolikor se uporabnik strinja,ga preusmerimo na naslednji korak
            if (dismiss)
                window.location.href = href;

        });
    } else {
        window.location.href = href;
    }

}

/**
 * Shrani komentar k hierarhiji
 */
function shraniKomentar() {

    var komentar = $('#hierarhija-komentar').val();
    var id = $('#hierarhija-komentar').attr('data-id');

    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=komentar-k-hierarhiji&m=post", {
        id: id,
        komentar: komentar
    }).success(function (podatki) {
        if (podatki == 1) {
            // zapremo popup
            $('#fade').fadeOut('slow');
            $('#vrednost_edit').hide();
        }
    });
}

/**
 * Predogled emaila za učitelje in managerje
 *
 *  1 - email za učitelje na zadnjem nivoju
 *  2 - email za managerje na vseh ostalih nivojih
 *
 * @param int vrsta - za katero vrsta emaila gre
 */
function previewMail(vrsta) {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=ostalo&m=preview-mail', {
        vrsta: vrsta,
    });
}


/************************************************
 Hierarhija - jstree, bootstrap select
 ************************************************/
function jstree_json_data(anketa, refresh) {
    $.ajax({
        async: true,
        type: "GET",
        url: "ajax.php?anketa=" + anketa + "&t=hierarhija-ajax&a=json_jstree",
        dataType: "json",
        success: function (json) {
            if (typeof refresh === 'undefined') {
                jstree_vkljuci(json);
            }
            else {
                //v kolikor imamo jsTree že postavljen samo osvežimo podatke
                var $jstree = $('#admin_hierarhija_jstree').jstree(true);
                $jstree.settings.core.data = json;
                $jstree.refresh();
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            console.log(thrownError);
        }
    });
}

function jstree_vkljuci(jsonData) {
    $("#admin_hierarhija_jstree").jstree({
        //'plugins': ['contextmenu', 'dnd', 'massload', 'sort', 'types'],
        'plugins': ['search', 'massload', 'contextmenu'],
        'contextmenu': {
            "items": function ($node) {
                return {
                    "Delete": {
                        "label": "Briši",
                        "action": function (data) {
                            var ref = $.jstree.reference(data.reference),
                                sel = ref.get_selected();
                            if (!sel.length) {
                                return false;
                            }
                            ref.delete_node(sel);

                            var url = window.location.href;
                            var par = url.match(/(?:anketa=)(\w+)/g);
                            var anketa_id = par[0].slice(7, par[0].length);

                            //pošljemo POST ukaz, da pobrišemo element
                            brisiVrsticoHierarhije($node.id);
                        }
                    },
                    //"Edit": {
                    //    "label": "Urejanje uporabnika",
                    //    'action': function () {
                    //
                    //
                    //    }
                    //}
                }
            }
        },
        'core': {
            "animation": 0,
            "check_callback": true,
            "expand_selected_onload": true,
            "themes": {
                "name": "proton",
                "responsive": true
            },
            "data": jsonData,
        },
        //"types": {
        //    "#": {
        //        "max_children": 1,
        //        "max_depth": 20,
        //        "valid_children": ["root"]
        //    },
        //    "root": {
        //        "icon": "glyphicon glyphicon-home",
        //        "valid_children": ["default"]
        //    },
        //    "default": {
        //        "valid_children": ["default", "file"]
        //    },
        //    "file": {
        //        "icon": "glyphicon glyphicon-home",
        //        "valid_children": []
        //    }
        //}
    }).on('loaded.jstree', function () {
        $("#admin_hierarhija_jstree").jstree('open_all');
    }).bind("select_node.jstree", function (event, data) {
        //V kolikor kliknemo na hierarhijo z levim miškinim klikom, potem v meniju select izberemo ustrezne vrednosti
        // ko vrednost zberemo iz jstree je potrebno baziti, da preverimo, če je neznan event, ker v nasprotnem primeru submit sproži omenjeno skripto
        if (event.isTrigger == 2 && (typeof data.event !== "undefined")) {
            //Pošljemo id, kamor je bil izveden klik in nato prikažemo ustrezne select opcije
            var url = window.location.href;
            var par = url.match(/(?:anketa=)(\w+)/g);
            var anketa_id = par[0].slice(7, par[0].length);

            $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=posodobi_sifrante", {
                id: data.node.id
            }).success(function (podatki) {
                var sifrant = JSON.parse(podatki);
                var st_naprej = 2;


                // najprej je potrebno vsa polja skriti, da nato prikažemo samo potrebna
                for (var st = 1; st <= sifrant.user.max_level; st++) {
                    $('.h-nivo-' + st).val('').trigger("liszt:updated"); //update chosen list -> v novejših verzijah je chosen:update
                    $('.h-level-' + st).removeClass('h-selected').hide();
                }

                //naredimo zanko po vseh nivojih
                $.each(sifrant, function (key, val) {
                    //izluščimo samo številke,ker uporabnika ne potrebujemo
                    if ($.isNumeric(key)) {
                        $('.h-level-' + key).addClass('h-selected').show();
                        $('.h-nivo-' + key).val(val.select).chosen().trigger("liszt:updated");
                    }
                });

                // prikažemo še možnost vnos naslednjega elementa
                var naslednjiSifrant = data.node.parents.length + 1;

                // Če uporabnik ni admin, potem ime ŠOLE ne vnesemo v HIERARHIJO in zato nam prikaže en element premalo in je potrebno +1, da nam prikaže možnost vnosa tudi naslednjega elementa
                if (sifrant.user.id_strukture != 'admin')
                    naslednjiSifrant += 1;

                $('.h-level-' + naslednjiSifrant).addClass('h-selected').show();
                $('.h-nivo-' + naslednjiSifrant).val('').chosen();


            });

        }
    });

}

function dodajKomentar() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').wrapAll('<div class="fixed-position"></div>').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=komentar-k-hierarhiji&m=get');
}

/**
 * Odpre popup za nalaganje logotipa
 */
function uploadLogo() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').wrapAll('<div class="fixed-position"></div>').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=upload-logo&m=get', function () {

        //Vklopi nice input file
        $("input[type=file]").nicefileinput({
            label: 'Poišči datoteko...'
        });

    });
}

/**
 * Izbriše logotip, ki je že naložen
 * @param $id
 */
function izbrisiLogo($id) {
    var id = $('form > input[name="id"]').val();

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=upload-logo&m=delete', {
        id: id
    }).success(function () {
        $('#hierarhija-logo').remove();
    });
}
/****************************  HIERARHIJA END ***************************/

function printElement(ime) {
    var divToPrint = $('.printElement');

    if (ime == 'Status') {
        var objekt = divToPrint.html();
        divToPrint = '<table class="printTable" id="printHierarhijaStatus">' + objekt + '</table>';
    } else if (ime == 'Analize') {
        divToPrint = document.getElementById('div_means_data').innerHTML;
    }

    var newWin = window.open('', ime, 'scrollbars=1');

    newWin.document.write('<html><head><title>Okno za tiskanje - ' + ime + '</title>');
    newWin.document.write('<meta http-equiv="Cache-Control" content="no-store"/>');
    newWin.document.write('<meta http-equiv="Pragma" content="no-cache"/>');
    newWin.document.write('<meta http-equiv="Expires" content="0"/>');

    newWin.document.write('<link rel="stylesheet" href="css/print.css#13042017">');
    newWin.document.write('<link rel="stylesheet" href="css/style_print.css" media="print">');
    newWin.document.write('</head><body class="print_analiza">');
    newWin.document.write('<div id="printIcon">');
    newWin.document.write('<a href="#" onclick="window.print(); return false;">Natisni</a>');
    newWin.document.write('</div>');

    newWin.document.write(divToPrint);
    newWin.document.write('</body></html>');
    newWin.focus();

    newWin.document.close();

}

/**
 * Posodobi nastavitve v bazi, za pošiljanje kod samo za učitelja ali tudi za vse
 *
 * @param string {vrednost}
 */
function posodobiPosiljanjeKod(vrednost, val) {
    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=ostalo&m=opcije', {
        name: vrednost,
        value: val,
        method: (val == 0 ? 'delete' : '')
    }).success(function () {
        $('#poslji-kode').val(vrednost);
    });
}

/**
 * POšlji obvestilo učiteljem, kateri še niso bili obveščeni
 *
 * @param {}
 * @return
 */
function obvestiUciteljeZaResevanjeAnkete() {
    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=ostalo&m=poslji-email-samo-uciteljem').success(function () {
        $('#obvesti-samo-ucitelje').html('<span style="color:#fa4913;">Elektronsko sporočilo s kodo je bilo posredovano vsem učiteljem, ki so na zgornjem seznamu</span>');
    });
}
// ker aplikacije ne sprejema JSON potem vuejs emulira json in pošlje kot navadno polje
Vue.http.options.emulateJSON = true;

// select2 direktiva
Vue.directive('select', {
    twoWay: true,
    priority: 1000,

    params: ['options'],

    bind: function () {
        var that = this;


        $(this.el).select2({
            width: '100%'
        }).on('change', function () {
            that.set(this.value)
        });

    },
    update: function (value) {
        $(this.el).val(value).trigger('change');
    },
    unbind: function () {
        $(this.el).off().select2('destroy')
    }
});



// Definiramo globalne spremenljivke za Vuejs
var gradnjaHierarhijeApp = '';
var hierarhijaApp = '';

$(document).ready(function () {
    if (document.querySelector('#hierarhija-app')) {
        hierarhijaApp = new Vue({
            el: '#hierarhija-app',
            data: {
                novaHierarhijaSt: 1, // številka prve ravni je vedno default 1, in pomeni da še nimamo nobenega vpisa ravni
                inputNivo: [],
                anketaId: $('#srv_meta_anketa_id').val(),
                sifrant: '',
                imeHierarhije: {
                    shrani: '',
                    aktivna: '',
                    index: '-1',
                    id: '-1',
                    urejanje: false,
                    editTitle: false
                },
                prikaziImeZaShranjevanje: false,
                shranjenaHierarhija: [
                    {
                        id: 'default',
                        ime: 'Hierarhija Šolski center',
                        anketa: '',
                        stUporabnikov: 0,
                        hierarhija: [
                            {
                                st: 1,
                                ime: 'Šolski center',
                                sifranti: [
                                    {ime: 'Ljubljana'},
                                    {ime: 'Maribor'},
                                    {ime: 'Koper'}
                                ]
                            },
                            {
                                st: 2,
                                ime: 'Šola',
                                sifranti: [
                                    {ime: 'Gimnazija'},
                                    {ime: 'Poklicna šola'}
                                ]
                            },
                            {
                                st: 3,
                                ime: 'Program',
                                sifranti: [
                                    {ime: 'Gimnazijec'},
                                    {ime: 'Fizik'}
                                ]
                            },
                            {
                                st: 4,
                                ime: 'Letnik',
                                sifranti: [
                                    {ime: '1. letnik'},
                                    {ime: '2. letnik'},
                                    {ime: '3. letnik'},
                                    {ime: '4. letnik'}
                                ]
                            },
                            {
                                st: 5,
                                ime: 'Razred',
                                sifranti: [
                                    {ime: 'a'},
                                    {ime: 'b'},
                                    {ime: 'c'},
                                    {ime: 'd'}
                                ]
                            },
                            {
                                st: 6,
                                ime: 'Predmet',
                                sifranti: [
                                    {ime: 'mat'},
                                    {ime: 'fiz'},
                                    {ime: 'slo'},
                                    {ime: 'geo'}
                                ]
                            }
                        ]
                    },
                    {
                        id: 'default',
                        ime: 'Šola',
                        anketa: '',
                        stUporabnikov: 0,
                        hierarhija: [
                            {
                                st: 1,
                                ime: 'Letnik',
                                sifranti: [
                                    {ime: '1. letnik'},
                                    {ime: '2. letnik'},
                                    {ime: '3. letnik'},
                                    {ime: '4. letnik'}
                                ]
                            },
                            {
                                st: 2,
                                ime: 'Razred',
                                sifranti: [
                                    {ime: 'a'},
                                    {ime: 'b'},
                                    {ime: 'c'},
                                    {ime: 'd'}
                                ]
                            },
                            {
                                st: 3,
                                ime: 'Predmet',
                                sifranti: [
                                    {ime: 'mat'},
                                    {ime: 'fiz'},
                                    {ime: 'slo'},
                                    {ime: 'geo'}
                                ]
                            }
                        ]
                    }
                ],
                defaultHierarhija: '',
                // omogočimo predogled hierarhije
                previewHierarhije: {
                    vklop: true,
                    input: [],
                    ime: '',
                    index: '',
                    id: '',
                    uporabniki: 0
                },

                imeNivoja: '',
                brisanjeDropdownMenija: [], // ali je opcija za meni vklopljena ali izklopljena
                vklopiUrejanje: true, // Vklopimo možnost urejanja preimenovanja
                vpisanaStruktura: false, // pove nam če je vpisana struktura oz. so dodani uporabniki na hierarhijo
                kopirajTudiUporabnike: 0, // iz seznama shranjenih hierarhij kopiramo tudi uporabnike/strukturo, če je seveda shranjena
            },

            // watch:{
            //     'imeHierarhije.shrani':function(val, oldVal){
            //         this.imeHierarhije.aktivna = val;
            //     }
            // },
            ready: function () {
                // Pridobi število nivojev
                this.pridobiStNivojev();

                var that = this;
                // Pridobi nivoje in podatke
                this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=json_nivoji_podatki').success(function (data) {
                    if (data != 'undefined' && data.length > 0) {
                        $.each(data, function (index, value) {
                            that.inputNivo.push(value);
                        });
                    }
                });

                // Pridobimo shranjene hierarhije v bazi
                this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=json_shranjene_hierarhije').success(function (data) {
                    if (data != 'undefined' && data.length > 0) {
                        $.each(data, function (index, value) {
                            that.shranjenaHierarhija.push(value);
                        });
                    }
                });


                // pridobimo vse nastavitve iz baze
                this.vseNastavitveIzBaze();
            },

            // Pridobimo trenutno število nivojev in dodamo novega
            methods: {
                // Omogoči možnost preimenovanja ankete
                editTitleToogle: function () {
                    return this.imeHierarhije.editTitle = !this.imeHierarhije.editTitle;
                },

                dodajNivoHierarhije: function (st) {
                    var that = this;
                    var ime = this.imeNivoja || 'nivo';
                    var st = this.novaHierarhijaSt;
                    this.imeNivoja = '';

                    // POST request
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=post_nivoji', {
                        nivo: st,
                        ime: ime
                    }).success(function (data) {
                        // ko dobimo id od ravni potem napolnimo dom element inputNivo
                        that.inputNivo.push({
                            st: st,
                            ime: ime,
                            id: data,
                            sifranti: []
                        });

                        // posodobimo število nivojev
                        that.pridobiStNivojev();

                    });

                },

                odstraniNivoHierarhije: function (index, id) {
                    var st = this.inputNivo[index].st;

                    this.inputNivo.forEach(function (obj) {
                        if (obj.st > st)
                            obj.st = obj.st - 1;
                    });

                    var that = this;


                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=brisi_nivo_hierarhija', {
                        id_nivoja: id
                    }).then(function (response) {
                        if (response.status == 200 && response.data == 0) {
                            swal({
                                title: "Napaka!",
                                text: "Omenjen nivo ni mogoče izbrisati, ker je že uporabljen pri izgradnji hierarhije.",
                                type: "warning",
                                confirmButtonText: "OK"
                            });
                        } else {
                            that.inputNivo.splice(index, 1);
                            that.novaHierarhijaSt = (that.novaHierarhijaSt - 1);
                        }
                    });
                },

                // izbrišemo vse ravni v hierarhiji, da lahko uporabnik na novo ustvarja
                izbrisiCelotnoHierarhijo: function () {

                    // Prejšno hierarhijo vedno shranimo
                    if (this.inputNivo.length > 0)
                        this.shraniTrenutnoHierarhijo();

                    // Če uporabnik ne vpiše imena potem obstoječo ne brišemo
                    if (this.pobrisiHierarhijoInZacniNovo()) {
                        // Vse spremenljivke postavimo na 0
                        this.imeHierarhije = {
                            aktivna: '',
                            shrani: '',
                            index: '-1',
                            id: '-1'
                        };

                        this.previewHierarhije.vklop = false;
                    }
                },

                // PObrišemo trenutno aktivno hierarhijo in začnemo novo, ki jo tudi shranimo za kasnejši preklic
                pobrisiHierarhijoInZacniNovo: function () {
                    var that = this;

                    //# V kolikor dela novo hierarhijo potem vedno prikažemo možnost za vpis imena
                    swal({
                        title: "Nova hierarhija",
                        text: "Vpišite ime nove hierarhije:",
                        type: "input",
                        animation: "slide-from-top",
                        closeOnConfirm: false,
                        closeOnCancel: true,
                        showCancelButton: true,
                        cancelButtonText: 'Prekliči',
                        allowOutsideClick: true,
                        inputPlaceholder: "Primer: Hierarhija šola"
                    }, function (inputValue) {
                        if (inputValue === false) return false;
                        if (inputValue === "") {
                            swal.showInputError("Ime hierarhije je obvezno!");
                            return false
                        }

                        //# Pobrišemo vse ravni in vso trenutno hierarhij v kolikor vpiše novo
                        that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=izbrisi_vse_ravni');

                        // Ime hierarhije shranimo v vue spremenljivko
                        that.getSaveOptions('aktivna_hierarhija_ime', inputValue);
                        that.imeHierarhije.shrani = inputValue;

                        // Ime hierarhije shranimo tudi v srv_hierarhija_shrani, da dobimo ID vnosa, kamor potem shranjujemo json podatke z vsemi šifranti in nivoji
                        that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=shrani_hierarhijo', {
                            ime: inputValue,
                            hierarhija: null
                        }).success(function (id) {
                            // shranimo tudi ID hierarhije
                            that.getSaveOptions('srv_hierarhija_shrani_id', id);
                        });


                        location.reload();
                    });


                },

                // Dodamo šifrant k ustreznemu nivoju/ravni
                dodajSifrant: function (index, idNivoja) {
                    var text = $('[data-nivo="' + idNivoja + '"]').val();

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=post_sifranti', {
                        idNivoja: idNivoja,
                        imeSifranta: text
                    }).success(function (data) {
                        this.inputNivo[index].sifranti.push({
                            ime: text
                        });

                        $('[data-nivo="' + idNivoja + '"]').val('');

                        var opcije = '';
                        $.each(data, function (index, value) {
                            opcije += '<option value = "#">' + value + '</option>';
                        });

                        $('#nivo-' + idNivoja + ' td:eq( 1 )').html('<select name="nivo" data-opcije="' + idNivoja + '">' + opcije + '</select>');
                    });

                },

                brisiSifrant: function (idNivoja) {
                    var that = this;

                    // Toogle spremenljivka, ki prikaže urejanje ali drop down meni
                    if (typeof this.brisanjeDropdownMenija[idNivoja] == 'undefined')
                        this.brisanjeDropdownMenija[idNivoja] = false;

                    this.brisanjeDropdownMenija[idNivoja] = !this.brisanjeDropdownMenija[idNivoja];

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=brisi_sifrante', {
                        idNivoja: idNivoja,
                    }).success(function (data) {

                        if (that.brisanjeDropdownMenija[idNivoja]) {
                            var opcije = '<div class="sifranti-razmik"><ul>';
                            $.each(data, function (index, value) {
                                opcije += '<li class="sifranti-brisanje" data-sifrant="' + value.id + '"><span class="icon brisi-x" onclick="izbrisiSifrant(' + value.id + ')"></span>' + value.ime + '</li>';
                            });
                            opcije += '</ul></div>';

                            $('#nivo-' + idNivoja + ' td:eq( 1 )').html(opcije);
                        } else {
                            $('[data-nivo="' + idNivoja + '"]').val('');

                            var opcije = '';
                            $.each(data, function (index, value) {
                                opcije += '<option value = "#">' + value.ime + '</option>';
                            });

                            $('#nivo-' + idNivoja + ' td:eq( 1 )').html('<select name="nivo" data-opcije="' + idNivoja + '">' + opcije + '</select>');
                        }

                    });

                },

                posodobiUnikatnega: function (id, obj) {
                    if (obj.unikaten == 0) {
                        obj.unikaten = 1;
                    } else {
                        obj.unikaten = 0;
                    }

                    $.post("ajax.php?anketa=" + this.anketaId + "&t=hierarhija-ajax&a=popravi_nivo_hierarhija", {
                        id_nivoja: id,
                        unikaten: obj.unikaten
                    });
                },

                // posodobi ime labele nivoja
                preimenujLabeloNivoja: function (id) {
                    this.$http.post("ajax.php?anketa=" + this.anketaId + "&t=hierarhija-ajax&a=popravi_nivo_hierarhija", {
                        id_nivoja: id,
                        besedilo: $('[data-labela="' + id + '"]').text()
                    });
                },

                // Pridobimo število nivojev, ki je vpisano za izbrano anketo
                pridobiStNivojev: function () {
                    var that = this;
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=st_nivojev').success(function (data) {
                        that.novaHierarhijaSt = (data + 1);

                        if (data > 0)
                            that.previewHierarhije.vklop = false;

                    });
                },

                // Shranimo trenutno izdelano hierarhijo
                shraniTrenutnoHierarhijo: function (shraniPodIstiId) {
                    // Če želimo izvesti update ali create new
                    var shraniPodIstiId = shraniPodIstiId || false;

                    // V kolikor samo uporabimo checkbox in je še vedno isto potem naredimo update
                    if (this.imeHierarhije.shrani == this.imeHierarhije.aktivna)
                        shraniPodIstiId = true;

                    // preverimo, če je shranjena struktura potem
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=pridobi-shranjeno-hierarhijo-bool', {
                        id: this.imeHierarhije.id,
                        polje: 'struktura',
                    }).then(function (response) {

                        // UPDATE se vedno zgodi, kadar gremo naprej
                        if (shraniPodIstiId && this.imeHierarhije.index > 1 && this.imeHierarhije.index != 'default') {
                            return this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=update-aktivno-hierarhijo', {
                                id: this.imeHierarhije.id,
                                hierarhija: JSON.stringify(this.inputNivo)
                            }).success(function () {
                                // našo trenutno hierarhijo shranimo tudi v dom, da v kolikor uporabnik še enkrat izbere isto hierarhijo, da se mu naložijo isti elementi
                                this.shranjenaHierarhija[this.imeHierarhije.index].hierarhija = JSON.stringify(this.inputNivo);
                            });
                        }

                        // Hierarhijo shranimo na novo

                        // če hierarhije ne poimenujemo potem dobi privzeto ime Hierarhija in čas kopiranja ali pa ostoječe ime in čas kopiranja (Šola, Hierarhija Šolski center)
                        if (!this.prikaziImeZaShranjevanje) {
                            // Če je že kopija kakšne od preh hierarhije potem dobi obstoječe ime in  uro
                            var time = new Date();
                            if (this.imeHierarhije.aktivna.length > 0) {
                                //  ime_H:i:s"
                                var sekunde = ('0' + time.getSeconds()).slice(-2);
                                var minute = ('0' + time.getMinutes()).slice(-2);
                                var ure = ('0' + time.getHours()).slice(-2);

                                this.imeHierarhije.shrani = this.imeHierarhije.aktivna + '_' + ure + ':' + minute + ':' + sekunde;
                            } else {
                                // Drugače pa "Hierarhija - H:i:s"
                                this.imeHierarhije.shrani = 'Hierarhija - ' + time.getHours() + ':' + time.getMinutes() + ':' + time.getSeconds();
                            }
                        }

                        this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=shrani_hierarhijo', {
                            ime: this.imeHierarhije.shrani,
                            hierarhija: JSON.stringify(this.inputNivo)
                        }).then(function (responseShrani) {
                            this.imeHierarhije.aktivna = this.imeHierarhije.shrani

                            // SHRANJENO HIERARHIJO shranimo tudi v spremenljivko za kasnejši preklic
                            this.shranjenaHierarhija.push({
                                id: responseShrani.data,
                                ime: this.imeHierarhije.shrani,
                                hierarhija: (typeof this.inputNivo == 'string' ? JSON.stringify(this.inputNivo) : this.inputNivo),
                                anketa: this.anketaId,
                                dom: true,
                            });

                            this.imeHierarhije.index = (this.shranjenaHierarhija.length - 1);

                            // shranimo tudi ID hierarhije
                            this.getSaveOptions('srv_hierarhija_shrani_id', responseShrani.data);
                            this.imeHierarhije.id = responseShrani.data;
                        });

                        // Ime shranjene hierarhije shranimo tudi kot aktivno hierarhijo
                        this.getSaveOptions('aktivna_hierarhija_ime', this.imeHierarhije.shrani);
                    });


                },

                /*
                 * Gumb za premikanje naprej
                 */
                premikNaprej: function (ime) {

                    if (ime == 'uredi-uporabnike') {
                        this.shraniTrenutnoHierarhijo(false, true);

                        // Preusmerimo na urejanje uporabnikov in naredimo cel reload ter pobrišemo cache
                        window.location.replace(location.origin + location.pathname + "?anketa=" + this.anketaId + "&a=hierarhija_superadmin&m=uredi-uporabnike");
                    }
                },

                /*
                 * Uporabimo shranjeno hierarhijo iz seznama
                 */
                uporabiShranjenoHierarhijo: function (index, id, uporabniki) {
                    var that = this;

                    // Tukaj moram imeti podatke še o starih stvareh
                    this.imeHierarhije.id = id;
                    this.uporabnikiZaKopijo = uporabniki || 0;

                    if (this.vpisanaStruktura)
                        return swal({
                            title: "Opozorilo!",
                            text: "Pri omenjeni strukturi hierarhije so že dodani uporabniki in nove hierarhije ni več mogoče izbrati, lahko samo dopolnjujete obstoječo.",
                            type: "warning",
                            confirmButtonText: "Zapri"
                        });

                    // Kadar še nimamo vpisane nobene ravni
                    if (this.novaHierarhijaSt == 1)
                        return that.posodobiHierarhijo(index, id);

                    swal({
                        title: "Kopiranje hierarhije",
                        text: "Z nadaljevanjem se bo hierarhija skopirala v novo ime, obstoječa pa se bo avtomatsko shranila pod dosedanje ime.",
                        type: "info",
                        showCancelButton: true,
                        cancelButtonText: "Ne",
                        confirmButtonText: "Da, nadaljuj."
                    }, function (shrani) {

                        if (shrani) {
                            // V kolikor želi uporabnik shraniti trenutno hierarhijo in pustimo index kot je
                            that.shraniTrenutnoHierarhijo(true);

                            setTimeout(function () {
                                Vue.nextTick(function () {
                                    // Izberemo novo hierarhijo
                                    that.posodobiHierarhijo(index, id);
                                });
                            }, 100);

                        }

                    });
                },

                // Preglej shranjeno hierarhijo in ne shrani v bazo
                pregledShranjeneHierarhije: function (index, id, uporabniki) {
                    // Nastavitve trenutne strukture na katero je kliknil uporabnik shranimo v predogled, da se lahko uporabi v kolikor bi uporabnik želel uporabiti omenjeno hierarhijo
                    this.previewHierarhije = {
                        vklop: true,
                        ime: this.shranjenaHierarhija[index].ime,
                        index: index,
                        id: id,
                        uporabniki: uporabniki
                    };


                    if (typeof this.shranjenaHierarhija[index].hierarhija == 'object')
                        this.previewHierarhije.input = this.shranjenaHierarhija[index].hierarhija;
                    else
                        this.previewHierarhije.input = JSON.parse(this.shranjenaHierarhija[index].hierarhija);
                },

                // Izklopimo predogled hierarhije
                izklopiPredogled: function () {
                    this.previewHierarhije = {
                        vklop: false,
                        ime: '',
                        index: '',
                        id: '',
                        uporabniki: 0,
                        input: []
                    };
                },

                // Uporabnik je iz predogleda izbral željeno hierarhijo, ki se bo aktivirala
                aktivirajIzbranoHierarhijo: function () {
                    this.uporabiShranjenoHierarhijo(this.previewHierarhije.index, this.previewHierarhije.id, this.previewHierarhije.uporabniki);
                },

                posodobiHierarhijo: function (index, id) {
                    var that = this;

                    // dodamo active class
                    this.imeHierarhije.index = index;

                    // Če urejamo hierarhijo potem nič ne urejamo sql baze in klik na ime hierarhije omogoči samo preimenovanje in brisanje
                    if (this.imeHierarhije.urejanje)
                        return '';

                    // preimenujemo Hierarhijo
                    this.imeHierarhije.aktivna = this.shranjenaHierarhija[index].ime;

                    // // shranimo ime hierarhije in trenuten id izbrane hierarhije v opcije
                    // this.getSaveOptions('aktivna_hierarhija_ime', this.imeHierarhije.aktivna);
                    // this.getSaveOptions('srv_hierarhija_shrani_id', id);

                    // Kadar prikličemo hierarhijo, ki je prazna, smo izbrali samo ime potem nič ne vrnemo, vse postavimo na nič
                    if (this.shranjenaHierarhija[index].hierarhija == '') {
                        this.inputNivo = [];
                        this.novaHierarhijaSt = 1;
                        // naloži šifrante, ker imamo šifrante v JSON.stringfy moramo anredite revers v object in če je object potem samo zapišemo v spremenljivko, drugače pa delamo reverse
                    } else if ((index < 2 || id === 'default') && typeof this.shranjenaHierarhija[index].hierarhija == 'object') {
                        this.inputNivo = this.shranjenaHierarhija[index].hierarhija;
                    } else {
                        this.inputNivo = JSON.parse(this.shranjenaHierarhija[index].hierarhija);
                    }


                    // prevzeto ne kopira uporabnikov, samo če pote če potrdi iz seznama
                    this.kopirajTudiUporabnike = 0;

                    // pošljemo ravni in nivoje ter shranimo vse potrebno v
                    if (this.uporabnikiZaKopijo == 1) {
                        setTimeout(function () {
                            swal({
                                title: "Opozorilo!",
                                text: "Ali želite kopirati tudi strukturo uporabnikov?",
                                type: "info",
                                showCancelButton: true,
                                cancelButtonText: "Ne",
                                confirmButtonText: "Da, tudi uporabnike."
                            }, function (shrani) {

                                if (shrani)
                                    that.kopirajTudiUporabnike = 1;

                                Vue.nextTick(function () {
                                    that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=obnovi-hierarhijo', {
                                        hierarhija: that.inputNivo,
                                        uporabniki: that.kopirajTudiUporabnike,
                                        id: id
                                    }).success(function (data) {
                                        that.inputNivo = [];

                                        if (data != 'undefined' && data != '' && data.length > 0)
                                            $.each(data, function (index, value) {
                                                that.inputNivo.push(value);
                                            });

                                        that.shraniTrenutnoHierarhijo();

                                        // posodobimo število nivojev
                                        that.pridobiStNivojev();

                                    });
                                });

                            });
                        }, 100);
                    } else {
                        Vue.nextTick(function () {
                            that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=obnovi-hierarhijo', {
                                hierarhija: that.inputNivo,
                                uporabniki: that.kopirajTudiUporabnike,
                                id: id
                            }).success(function (data) {
                                that.inputNivo = [];

                                if (data != 'undefined' && data != '' && data.length > 0)
                                    $.each(data, function (index, value) {
                                        that.inputNivo.push(value);
                                    });

                                that.shraniTrenutnoHierarhijo();

                                // posodobimo število nivojev
                                that.pridobiStNivojev();

                            });
                        });

                    }

                },

                // shrani ali pridobi opcije iz baze
                getSaveOptions: function (option, value, response) {
                    if (typeof value != 'undefined' && typeof response == 'undefined')
                        response = 'save';

                    if (typeof value == 'undefined' && typeof response == 'undefined')
                        response = 'get';

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=hierarhija-options&m=' + response, {
                        option_name: option || '',
                        option_value: value || ''
                    }, function (data) {
                        return data;
                    });
                },

                // ko zapustimo urejanje/preimenovanje potem spremenimo tudi dom
                preimenujHierarhijo: function (index, id) {
                    var ime = $.trim($('.h-ime-shranjeno.editable-hierarhija').html());

                    //odstranimo html tag
                    var div = document.createElement('div');
                    div.innerHTML = ime;
                    ime = $.trim(div.innerText);

                    var ime_dom = this.shranjenaHierarhija[index].ime;

                    // V kolikor je bila preimenova aktivna anketa moramo tudi v bazi med opcijami preimenovati
                    if (this.imeHierarhije.aktivna == ime_dom)
                        this.getSaveOptions('aktivna_hierarhija_ime', ime);

                    // v kolikor je zbrisano celotno ime ponovno damo na default
                    if (id == 'default' || ime.length == 0 || this.shranjenaHierarhija[index].ime.length == 0)
                        return $('.h-ime-shranjeno.active').html(ime_dom);

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=preimenuj-hierarhijo', {
                        id: id,
                        ime: ime
                    }, function () {
                        //v kolikor smo v bazi uspešno preimenovali potem tudi v naši spremenljivki preimenujemo
                        this.shranjenaHierarhija[index].ime = ime;
                    });
                },

                izbrisiShranjenoHierarhijo: function (index, id) {
                    if (id == 'default' || id == this.imeHierarhije.id)
                        return '';

                    // post request, ki izbriše iz baze
                    var obvestilo = this.deleteHierarhijaShrani(id);

                    if (obvestilo)
                        this.shranjenaHierarhija.splice(index, 1);

                },

                // Uvoz in izviz hierarhije v CSV
                uvozHierarhije: function () {
                    $('#fade').fadeTo('slow', 1);
                    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=uvozi-hierarhijo', function () {

                        //Vklopi nice input file
                        $("input[type=file]").nicefileinput({
                            label: 'Poišči datoteko...'
                        });

                    });

                    var position = $(window).scrollTop();
                    $('#vrednost_edit').css('top', position + 300);
                },

                izvozHierarhije: function () {
                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=izvoz-hierarhije');
                },

                // pridobimo vse nastavitve iz baze
                vseNastavitveIzBaze: function () {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=hierarhija-options&m=get').success(function (data) {

                        $.each(data, function (index, value) {
                            if (index == 'aktivna_hierarhija_ime') {
                                // za prikaz naslova hierarhije
                                that.imeHierarhije.aktivna = value;

                                // polje za shranjevanje, da shrani v enako hierarhijo
                                that.imeHierarhije.shrani = value;

                                // Če imamo ime hierarhije potem nimamo predogleda
                                if (value.length > 0)
                                    that.previewHierarhije.vklop = false;

                                // that.imeHierarhije.index = (that.shranjenaHierarhija.length - 1);
                            }

                            if (index == 'admin_skrij_urejanje_nivojev')
                                that.vklopiUrejanje = (value == 'true' ? true : false);

                            if (index == 'srv_hierarhija_shrani_id') {
                                // na levi strani izbere ustrezno hierarhijo, moramo nastavit timeout, ker drugače ne pridobimo vseh hierarhij
                                setTimeout(function () {
                                    Vue.nextTick(function () {
                                        $.each(that.shranjenaHierarhija, function (i, val) {
                                            if (val.id == value) {
                                                that.imeHierarhije.index = i;
                                                that.imeHierarhije.id = value;
                                            }
                                        });
                                    });
                                }, 100);
                            }

                            // V kolikor imamo vpisano struktur
                            if (index == 'vpisana_struktura')
                                that.vpisanaStruktura = value;

                        });

                    });
                },

                posodobiOpcijeHierarhije: function () {
                    if (this.imeHierarhije.urejanje)
                        this.vseNastavitveIzBaze();
                },

                /**
                 * Če smo hierarhijo prvič aktivirali potem ponudi popup za vpis imena in shrani ime hierarhije v bazo
                 */
                hierarhijoSmoAktivirali: function () {
                    var that = this;

                    if (this.inputNivo.length == 0 && this.imeHierarhije.aktivna == '' && this.imeHierarhije.shrani == '')
                        swal({
                            title: "Nova hierarhija",
                            text: "Vpišite ime nove hierarhije:",
                            type: "input",
                            animation: "slide-from-top",
                            closeOnConfirm: false,
                            closeOnCancel: true,
                            inputPlaceholder: "Primer: Hierarhija šola"
                        }, function (inputValue) {
                            if (inputValue === false) return false;

                            if (inputValue === "") {
                                swal.showInputError("Ime hierarhije je obvezno!");
                                return false
                            }

                            // Ime hierarhije shranimo v vue spremenljivko
                            that.getSaveOptions('aktivna_hierarhija_ime', inputValue);
                            that.imeHierarhije.shrani = inputValue;
                            that.imeHierarhije.aktivna = inputValue;

                            swal.close();
                        });
                },


                /**
                 * Pobriše shranjeno hierarhijo v tabeli srv_hierarhija_shrani
                 */
                deleteHierarhijaShrani: function (id) {
                    var id = id || 0;
                    var obvestilo = false;

                    if (id == 0)
                        return console.log('brez Id-ja');

                    // post request, ki izbriše iz baze
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=izbrisi-hierarhijo', {
                        id: id
                    }).then(function (response) {
                        if (response.data == 'success')
                            obvestilo = true;

                        return obvestilo;
                    });

                    return obvestilo;
                },

                /**
                 * Dodaj komentar k hierarhiji
                 */
                dodajKomentar: function () {
                    dodajKomentar();
                },

                /**
                 * Odpre popup za nalaganje logotipa
                 */
                logoUpload: function () {
                    uploadLogo();
                }

            }

        });
    }


    if (document.querySelector('#vue-gradnja-hierarhije')) {
        gradnjaHierarhijeApp = new Vue({
            el: '#vue-gradnja-hierarhije',
            data: {
                anketaId: $('#srv_meta_anketa_id').val(),
                pageLoadComplete: false,
                vpisanaStruktura: false, // pove nam, če je uporabnik že vpisal kakšno strukturo, da s tem zaklenemo vpis novih ravni (obstoječa struktura ne bi bila ok)
                izbran: {
                    skrij: 1,
                    sifrant: [],
                    strukturaId: [],
                    sifrantPodatki: [],
                    parent: [],
                },
                // tukaj vpišemo št. nivoja, ki je key in nato sifrante
                podatki: [],

                // V kolikor uporabnik ni superadmin/admin potem podtke, ki so nad njegovim ali enake njegovemu nivoju pridobimo kot fiksne in se jih ne da spreminjati
                fiksniPodatki: [],

                // pri vpisu oseb na ustrezni nivo
                osebe: {
                    prikazi: false,
                    nivo: 0,
                    vpisane: [], // key je številka nivoja, in potem notri imam object s podatki o osebah
                    nove: [], // key je številka nivoja in nato notri object s podatki o osebah
                    textarea: '',
                    show: [] // boolean, glede na nivo, da pokaže uporabnike pod šifranti
                },

                // podatki o uporabniku, ki ni admin
                user: {
                    struktura: [],
                    uporabnik: [],
                    dropdown: [],
                    selected: ''
                },

                // vpis emaila preko textarea
                email: {
                    napake: [],
                    opozorilo: false
                },

                elektronskiNaslovi: [{
                    email: "prvi@email.si",
                    ime: "Prvo Ime"
                }, {
                    email: "drugi@email.si",
                    ime: "Drugi email"
                }],

            },
            watch: {
                'user.selected': function (val) {

                    if (typeof val !== 'undefined' && val !== null && val.length > 0)
                        this.vpisemoUporabnikaIzDropDownMenija();

                }
            },
            computed: {},

            ready: function () {
                var that = this;

                // Pridobimo omejitve uporabnika
                this.preveriNivoInPraviceUporabnika();

                // Pridobimo vse nivoje in šifrante neglede na status uporabnika
                this.naloziVseNivojeInSifrante();

                // Ko je celoten JS in spletna stran naložena potem spremenimo select2 change event, da deluje
                document.onreadystatechange = function () {

                    // Ko je stran čisto naložena izvedemo kodo
                    if (document.readyState === 'complete') {

                        // potrebno, ker drugače v FF in IE stvar ne deluje, da je zakasnitev 300ms in se počaka potem na naslednjo spremembo
                        setTimeout(function () {
                            Vue.nextTick(function () {

                                // Prikažemo prvi nivo
                                that.pageLoadComplete = true;

                                // Select 2 event
                                $(".select2").on('change', function () {

                                    // uogtotovimo, kje smo spremenili podatek
                                    var st = that.izbran.sifrant.length;
                                    var level = $(this).attr('data-level');

                                    that.izbran.sifrant.forEach(function (value, key) {
                                        if (key > level) {
                                            for (var i = key; i < st; i++) {
                                                that.izbran.sifrantPodatki.$set(i, null);
                                                that.izbran.sifrant[i] = "";
                                            }
                                        }
                                    });

                                    // Zanka po vseh nivojih, kateri so vpisani
                                    that.izbran.sifrant.forEach(function (value, key) {
                                        if (typeof value != 'undefined' && value.length > 0 && !isNaN(value) && that.izbran.sifrant[key].length > 0) {
                                            that.preveriSifrantZaIzbranNivo(value, key)
                                        }
                                    });

                                });
                            });
                        }, 600);

                        // Dodamo še možnost helpa v kolikor obstaja
                        load_help();
                    }
                }

                // Pridobi, če so že vpisani šifranti
                this.pridobiNastavitveCeJeVpisanaStruktura();

                // Pridobimo uporabnikeza dropdown meni user
                this.pridobiUporabnikeZaDropdownList();

            },

            methods: {
                // Preverimo, če je uporabnik admin ali je uporabnik s pravicami na določenem nivoju
                preveriNivoInPraviceUporabnika: function () {
                    var that = this;

                    // preverimo pravico in pridobimo že vpisano strukturo nad uporabikom
                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-user-level', function (data) {
                        // pridobimo polji (uporabnik, struktura), v kolikor je admin ni podatka o strukturi
                        that.user = data;

                        if (data.uporabnik != 1 && data.struktura) {
                            // ID strukture, ki je fiksna zapišemo v spremenljivko
                            data.struktura.forEach(function (val) {
                                that.izbran.strukturaId.$set(val.izbrani.level, val.izbrani.id);

                                // Že izbrano strukturo vpišemo v sifrantiPodatki, kjer se dodajajo tudi še na novo dodani podatki
                                that.izbran.sifrantPodatki.$set(val.izbrani.level, val.izbrani);
                            });

                        }
                    });
                },

                // Naložimo vse nivoje in pripadajoče šifrante
                naloziVseNivojeInSifrante: function () {
                    var that = this;

                    // pridobi šifrante za ustrezni nivo, če ni nič izbrano potem vedno pridobi šifrante za prvi nivo
                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-sifranti', function (data) {
                        data.nivoji.forEach(function (val) {
                            val.sifranti = [];

                            // vpišemo nivo in pdoatke nivoja
                            that.podatki.push(val);

                            // pole $(this.el).on('change', )g nivoja vpišemo še podatke o šifrantih
                            data.sifranti.forEach(function (options) {
                                // tukaj zapišemo šifrante na ustrezen nivo, edino tukaj upoštevamo, da številka nivoja je za 1 manšja, ker če 0 pustimo potem pri prikazuso težave, nivo 1 je element 0
                                if (val.level == options.level)
                                    that.podatki[(val.level - 1)].sifranti.push(options);
                            });
                        });

                        // Max število nivojev za validacije
                        that.podatki.maxLevel = data.maxLevel;
                    });
                },

                // Preveri, če je šifrant za izbran nivo že vpisan v podatkovno bazo
                preveriSifrantZaIzbranNivo: function (sifrant, nivo) {
                    var that = this;
                    // Parent vedno vzamemo id elementa, ki je vpisan en nivo prej
                    var parent_id = (this.izbran.sifrantPodatki[nivo - 1] ? this.izbran.sifrantPodatki[nivo - 1].id : null);

                    Vue.nextTick(function () {
                        // var parent_id2 = (that.izbran.sifrantPodatki[nivo - 1] ? that.izbran.sifrantPodatki[nivo - 1].id : null);

                        that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=preveri-sifrant-za-nivo', {
                            level: nivo,
                            hierarhija_sifranti_id: sifrant,
                            parent_id: parent_id
                        }).then(function (i) {
                            if (i.data == 0) {
                                // V kolikor omenjen id šifranta še ne obstaja v strukturi potem shranimo v polje novSifrant, da ga pri sumbitu upoštevamo
                                that.izbran.sifrantPodatki.$set(nivo, {
                                    id: null,
                                    level: nivo,
                                    hierarhija_sifranti_id: sifrant,
                                    hierarhija_ravni_id: that.podatki[nivo - 1].id,
                                    parent_id: parent_id
                                });
                            } else {
                                // shranimo na ključ, kjer je nivo celo polje
                                that.izbran.sifrantPodatki.$set(i.data.level, i.data);
                            }

                            // Preverimo, za nivo, če lahko prikažemo uporabnike
                            that.prikaziUporabnike(nivo);
                        });
                        // DOM updated
                    });

                },

                // Potrdimo vpis šifrantov, ki smo jih izbrali
                submitSifrante: function () {
                    var that = this;

                    // Preverimo, če je bil dodan kak nov elemepridobiIdSifrantovInUporabnikent
                    var prestejNove = 0;
                    this.izbran.sifrantPodatki.forEach(function (val) {
                        if (val != null && val.id == null && !isNaN(val.id))
                            prestejNove++;
                    });

                    if (prestejNove == 0)
                        return swal({
                            title: "Opozorilo!",
                            text: "<div style='text-align: left;'>Vse vrstice so že prenesene v hierarhijo:" +
                            "<ul><li>Bodisi vnesite novega učitelja in njegov predmet.</li>" +
                            "<li>Bodisi zaključite z vnosom in s klikom na gumb NAPREJ (spdaj desno) aktivirajte hierarhijo.</li></ul></div>",
                            type: "error",
                            html: true
                        });

                    var st = this.podatki.maxLevel;
                    // Če je vnešen zadnji nivo, object ni null in ni vpisanih oseb, ker na zadnjem nivo morajo biti vpisane osebe
                    if (that.izbran.sifrantPodatki[st] != null && (typeof this.osebe.nove[st] == 'undefined' || this.osebe.nove[st].length == 0))
                        return swal({
                            title: "Opozorilo!",
                            text: "Na zadnjem nivoju morate obvezno vpisati elektronski naslov osebe.",
                            type: "error"
                        });

                    // Izpišemo opozorilo, če uporabnik ni vnesel zadnjega nivoja
                    if (that.izbran.sifrantPodatki[st] == null)
                        swal({
                            title: "Opozorilo!",
                            text: "Niste vpisali zadnjega nivoja.",
                            type: "warning",
                            timer: 2000
                        });

                    // Posredujemo podatke za shranjevanje
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-struktura', {
                        vnos: that.izbran.sifrantPodatki,
                        osebe: that.osebe.nove
                    }).then(function () {
                        //Tukaj moramo osvežiti vse šifrante, v dataTables in JsTree, omenjeni funkciji sta v custom.js - splošni jquery
                        tabela.ajax.reload(null, false);
                        jstree_json_data(that.anketaId, 1);

                        // Če je bil izdan zadnji nivo od vseh mogočih potem odstranimo element izbire iz zadnjega nivoja
                        if (typeof that.izbran.sifrant[that.podatki.maxLevel] != 'undefined' && that.izbran.sifrant[that.podatki.maxLevel].length > 0) {
                            // Zadnji nivo odstranimo iz select2 izbire
                            that.izbran.sifrant.splice(that.podatki.maxLevel, 1);

                            // Izbrišemo tudi vse podatke o izbranem elementu iz DOM-a
                            that.izbran.sifrantPodatki.splice(that.podatki.maxLevel, 1);

                            //postavimo spremenljivko na true, da prikaže drugačen tekst pri navodilih
                            $('.srv_hierarchy_user_help').hide();
                            $('.srv_hierarchy_user_help_sifrant_vnesen').show();
                        }

                        // Osveži podatke o vseh šifrantih, ki so izbrani in so bili na novo dodani
                        that.preveriBazoZaSifrant(null, 1);

                        // Polje z na novo dodanimi osebami se izprazni
                        that.osebe.nove = [];

                        //Odstrani besedilo Uporabnik/i iz zadnjega polja, ker ga še tako odstranimo
                        that.osebe.show.$set(st, false);

                        // Zapišemo spremembo, da je struktura vnešena
                        that.aliJeStrukturaVnesena();

                        // Shanimo celotno strukturo v string in srv_hierarhija_shrani
                        that.shraniUporabnikeNaHierarhijo();

                    });
                },

                // Klik na ikono osebe, prikaže spodaj opcijo za vpis oseb
                prikaziVnosOseb: function (level) {
                    // V kolikor kliknemo na isto ikono 2x potem uporabimo toggle opcijo
                    if (level == this.osebe.nivo)
                        return this.osebe.prikazi = !this.osebe.prikazi;

                    this.osebe.prikazi = true;
                    return this.osebe.nivo = level;
                },

                vpisemoUporabnikaIzDropDownMenija: function () {

                    this.osebe.nove[this.osebe.nivo] =  [this.user.selected.split(',')];

                    // Prikažemo polje z uporabniki, ki so bili na novo dodani
                    this.prikaziUporabnike(this.osebe.nivo);

                    // Tekstovno polje spraznimo in ga skrijemo
                    this.user.selected = null;
                    this.osebe.prikazi = false;
                },

                vpisOsebNaNivoTextarea: function () {
                    var that = this;

                    // preverimo email in vrnemo napako, če obstaja
                    if (this.preveriPravilnostEmaila())
                        return this.email.opozorilo;

                    if (typeof this.user.selected !== 'undefined' && this.user.selected && this.user.selected.length > 0) {
                        var vpis = [this.user.selected];
                    } else {
                        // uporabnike razdelimo glede na \n in jih shranimo v polje
                        var vpis = this.osebe.textarea.split('\n');
                    }


                    this.osebe.nove.$set(that.osebe.nivo, []);
                    // ločimo še vejice
                    $.each(vpis, function (key, val) {
                        var loci = val.split(',');

                        // če je email večji od 4 znakov, ga shranimo kot novega drugače ne
                        if (loci[0].length > 4) {
                            that.osebe.nove[that.osebe.nivo].push(loci);
                        }
                    });

                    // Prikažemo polje z uporabniki, ki so bili na novo dodani
                    this.prikaziUporabnike(this.osebe.nivo);

                    // Tekstovno polje spraznimo in ga skrijemo
                    this.osebe.textarea = '';
                    this.osebe.prikazi = false;
                    this.user.selected = '';
                },

                // Preveri, če string ustreza pravilnemu zapis emaila
                preveriEmail: function (email) {
                    var EMAIL_REGEX = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

                    return EMAIL_REGEX.test(email);
                },

                // Preverimo pravilnost vpisanega emaila in vržemo napako
                preveriPravilnostEmaila: function () {
                    var that = this;

                    // uporabnike razdelimo glede na \n in jih shranimo v polje
                    var vpis = this.osebe.textarea.split('\n');

                    // vse napake postavimo na 0
                    this.email.napake = [];

                    // ločimo še vejice
                    $.each(vpis, function (key, val) {
                        var loci = val.split(',');

                        if (!that.preveriEmail(loci[0]) && loci[0].length > 0) {
                            that.email.napake.push({
                                naslov: loci[0],
                                vrstica: (key + 1)
                            });
                        }
                    });

                    // v kolikor so v poju zapisani napačni email naslovi potem prikažemo opozorilo
                    if (this.email.napake.length > 0)
                        return this.email.opozorilo = true;
                },

                // Preverimo, če uporabniki so že vpisani v bazi in jih prikažemo ali če so bili uporabniki na novo dodani
                prikaziUporabnike: function (level) {
                    // Uporabniki so bili na novo dodani na nivo
                    if (this.osebe.nove[level] && this.osebe.nove[level].length > 0)
                        return this.osebe.show.$set(level, true);

                    // imamo uporabni v SQL bazi
                    if (this.izbran.sifrantPodatki[level] && this.izbran.sifrantPodatki[level].uporabniki)
                        return this.osebe.show.$set(level, true);

                    return this.osebe.show.$set(level, false);
                },

                // Izbriši uporabnika iz this.osebe.nove
                izbrisiUporabnika: function (level) {
                    return this.osebe.nove.splice(level, 1);
                },

                // Izbriši uporabnika iz Sql baze, ker je že shranjen
                izbrisiUporabnikaIzBaze: function (userId, index, level) {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=brisi&m=uporabnika', {
                        uporabnik_id: userId,
                        struktura_id: this.izbran.sifrantPodatki[level].id
                    }).then(function () {
                        that.izbran.sifrantPodatki[level].uporabniki.splice(index, 1);
                    });

                },

                // Preverimo v SQL-u, da dobimo za vpisane šifrante ID in parent_id
                // Rekurzivna funkcija, ki ob sumbitu preveri v bazi in vsem še obstoječim šifrantom doda id in parent_id
                preveriBazoZaSifrant: function (parent_id, key) {
                    var that = this;

                    // Polje z omenjenim elementom mora obstajati, drugače smo prišli do konca
                    if (this.izbran.sifrantPodatki[key]) {

                        // V kolikor element že ima parent id, potem tega elementa ne preverjamo in gremo preverit naslednji element
                        // Prvi element vedno preverimo (key == 1)
                        if (key > 1 && this.izbran.sifrantPodatki[key] && this.izbran.sifrantPodatki[key].parent_id != 'null') {
                            var st = key + 1;
                            this.preveriBazoZaSifrant(this.izbran.sifrantPodatki[key].id, st);
                        }

                        // AJAX request, da preveri podatke o elementu in pridobi parent_id
                        this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=preveri-sifrant-za-nivo', {
                            level: this.izbran.sifrantPodatki[key].level,
                            hierarhija_sifranti_id: this.izbran.sifrantPodatki[key].hierarhija_sifranti_id,
                            parent_id: parent_id
                        }).then(function (i) {

                            // shranimo na ključ, kjer je nivo celo polje
                            that.izbran.sifrantPodatki.$set(i.data.level, i.data);

                            // V kolikor vsebuje podatke o uporabnikih potem te rudi prikaže
                            that.prikaziUporabnike(i.data.level);

                            // Pridobimo številko naslednjega elementa
                            var st = 1 + Number(i.data.level);

                            // Pokličemo rekurzivno funkcijo, da kjer je paren_id, id trenutnega elementa
                            that.preveriBazoZaSifrant(i.data.id, st);
                        });
                    }

                    return 0;
                },

                // pridobimo vse nastavitve iz baze
                pridobiNastavitveCeJeVpisanaStruktura: function () {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=hierarhija-options&m=get').success(function (data) {

                        if (data.length == 0)
                            return that.vpisanaStruktura = false;

                        $.each(data, function (index, value) {
                            if (index == 'vpisana_struktura')
                                that.vpisanaStruktura = value;
                        });
                    });
                },

                // Preveri, če obstaja med opcijami vpisana_struktura, drugače jo vnese
                aliJeStrukturaVnesena: function () {
                    if (this.vpisanaStruktura)
                        return this.vpisanaStruktura;

                    // V kolikor gre za vpis v bazo
                    hierarhijaApp.getSaveOptions('vpisana_struktura', 1);
                    this.vpisanaStruktura = 1;
                },

                // Preverimo, je izbran element za sledeči nivo, če je nivo večje kot zadnje nivo in če na zadnjem nivoju še ni vpisanega uporabnika potem dovoli prikaz ikone za vnos uporabnikov
                aliPrikazemIkonoZaDodajanjeUporabnikov: function (level) {
                    var level = level || false;

                    if (!level)
                        return false;

                    if (this.izbran.sifrant[level] > 0 &&
                        (level < this.podatki.maxLevel ||
                        level == this.podatki.maxLevel &&
                        this.izbran.sifrantPodatki[level] &&
                        !this.izbran.sifrantPodatki[level].hasOwnProperty('uporabniki'))
                    )
                        return true;

                    return false;
                },

                /*
                 * Pridobimo vse ID-je že vpisanih šifrantov skupaj z uporabniki
                 * izhajamo pa iz zadnjega ID-ja
                 */
                pridobiIdSifrantovInUporabnike: function (idLast) {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=kopiranje-vrstice', {
                        id: idLast
                    }).then(function (response) {
                        // response ok in imamo objekt
                        if (response.status == 200 && response.data.length > 0) {
                            response.data.forEach(function (val) {
                                that.izbran.sifrantPodatki.$set(val.izbrani.level, val.izbrani);
                                $('option[value="' + val.izbrani.hierarhija_sifranti_id + '"]').parent().val(val.izbrani.hierarhija_sifranti_id).trigger('change');
                            });

                            $(window).scrollTop(0);
                        }
                    });

                },

                /**
                 * Shranimo celotno strukturo z uporabniki v srv_hierarhija_shrani
                 */
                shraniUporabnikeNaHierarhijo: function () {

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=shrani-strukturo-hierarhije', {
                        id: this.anketaId,
                        shrani: 1
                    });
                },

                /**
                 * Pridobimo uporabnike, ki jih imamo shranjene v bazi za drop down list
                 */
                pridobiUporabnikeZaDropdownList: function () {
                    var that = this;

                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=import-user&s=getAll').success(function (data) {
                        that.user.dropdown = data;
                    });
                },


                /**************** funkcije, ki preveri true/false **************/
                preveriCejeEmailZeVnesenVbazoZaUcitelja: function (maxLevel) {
                    var maxLevel = maxLevel || 0;

                    if(maxLevel === 0 || this.izbran.sifrantPodatki[maxLevel] !== null)
                        return false;

                    if(this.izbran.sifrantPodatki[maxLevel] !== null && this.izbran.sifrantPodatki[maxLevel].uporabniki.length > 0)
                        return true;

                    return false;
                },

                prikaziJsKoSeJeCelaSpletnaStranZeNalozila: function(level){
                    var level = level || 0;

                    if((level == 1 && this.pageLoadComplete) || (this.izbran.sifrant[level-1] > 0 && this.izbran.sifrant[level-1].length > 0))
                        return true;

                    return false;
                },

                prikaziSelectZaZadnjiNivo: function(level) {
                    var level = level || 0;
                    this.osebe.nivo = level;

                    var prikazi =  this.aliPrikazemIkonoZaDodajanjeUporabnikov(level);

                    if(level === this.podatki.maxLevel && this.user.dropdown && prikazi)
                        return true;

                    return false;
                },

            },
        });
    }

    if (document.querySelector('#vue-custom')) {
        new Vue({
            el: '#vue-custom',
            data: {
                anketaId: $('#srv_meta_anketa_id').val(),
                managerOznaciVse: true,
                statusTabela: '',
                supersifra: [],
            },
            methods: {
                managerZamenjajOznaci: function () {
                    return this.managerOznaciVse = !this.managerOznaciVse;
                },
                emailObvestiloZaManagerje: function () {
                    event.preventDefault();

                    var polje = [];
                    $('[name="manager"]:checked').each(function () {
                        polje.push($(this).val());
                    });

                    //Poljšemo podatke
                    $.post("ajax.php?anketa=" + this.anketaId + "&t=hierarhija-ajax&a=ostalo&m=obvesti-managerje", {
                        managerji: polje
                    }).then(function (response) {
                        $('[name="manager"]:checked').each(function () {
                            $(this).hide();
                            $(this).parent().prepend('<span> - </span>');
                        });

                        if (response.data == 'success') {
                            swal({
                                title: "Obvestilo poslano!",
                                text: "Elektronsko sporočilo je bilo uspešno poslano.",
                                type: "success",
                                timer: 3000
                            });
                        }

                    });

                }
            }
        });
    }
});

function izbrisiSifrant(id) {
    var anketa_id = $('#srv_meta_anketa_id').val();
    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=izbrisi_sifrant", {
        idSifranta: id
    }).then(function (response) {
        if (response == 1)
            return swal({
                title: "Opozorilo!",
                text: "Šifrant je že uporabljen in ga ni mogoče izbrisati.",
                type: "error",
                timer: 3000
            });

        $('[data-sifrant="' + id + '"]').remove();
    });
}

// Pobriše vrstico iz DataTables in odstrani šifrante iz vseh nivojev pri izbiri
function pobrisiVrsticoHierarhije(id) {
    gradnjaHierarhijeApp.$set('izbran.sifrant', []);
    gradnjaHierarhijeApp.$set('izbran.sifrantPodatki', []);

    // V kolikor gre za uporabnika na nižjem nivoju potem moramo ponovno pridobiti strukturo in vse podatke o fiksnih nivojih
    gradnjaHierarhijeApp.preveriNivoInPraviceUporabnika();

    brisiVrsticoHierarhije(id, 1);
}