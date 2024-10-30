(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('vue')) :
  typeof define === 'function' && define.amd ? define(['exports', 'vue'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global["vue-sidebar-menu"] = {}, global.Vue));
})(this, (function (exports, vue) { 'use strict';

  var initSidebar = function initSidebar(props, emits) {
    var _toRefs = vue.toRefs(props),
      collapsed = _toRefs.collapsed,
      relative = _toRefs.relative,
      width = _toRefs.width,
      widthCollapsed = _toRefs.widthCollapsed,
      rtl = _toRefs.rtl;
    var sidebarRef = vue.ref(null);
    var isCollapsed = vue.ref(collapsed.value);
    var mobileItem = vue.reactive({
      item: null,
      rect: {
        top: 0,
        height: 0,
        padding: [0, 0],
        maxHeight: 0,
        maxWidth: 0,
        dropup: 0
      },
      timeout: null
    });
    var getMobileItem = vue.computed(function () {
      return mobileItem.item;
    });
    var getMobileItemRect = vue.computed(function () {
      return mobileItem.rect;
    });
    var currentRoute = vue.ref('');
    var updateIsCollapsed = function updateIsCollapsed(val) {
      isCollapsed.value = val;
    };
    var setMobileItem = function setMobileItem(_ref) {
      var item = _ref.item,
        itemEl = _ref.itemEl;
      clearMobileItemTimeout();
      var linkEl = itemEl.children[0];
      var rect = getMobileItemRectFromEl(linkEl);
      updateMobileItem(item);
      updateMobileItemRect(rect);
    };
    var getMobileItemRectFromEl = function getMobileItemRectFromEl(el) {
      var _el$getBoundingClient = el.getBoundingClientRect(),
        elTop = _el$getBoundingClient.top,
        elBottom = _el$getBoundingClient.bottom,
        elHeight = _el$getBoundingClient.height;
      var _sidebarRef$value$get = sidebarRef.value.getBoundingClientRect(),
        sidebarLeft = _sidebarRef$value$get.left,
        sidebarRight = _sidebarRef$value$get.right;
      var _sidebarRef$value$fir = sidebarRef.value.firstElementChild.getBoundingClientRect(),
        wrapperBottom = _sidebarRef$value$fir.bottom,
        wrapperHeight = _sidebarRef$value$fir.height;
      var scrollWrapperEl = el.offsetParent;
      var scrollWrapperOffsetTop = scrollWrapperEl.offsetTop;
      var _scrollWrapperEl$getB = scrollWrapperEl.getBoundingClientRect(),
        scrollWrapperTop = _scrollWrapperEl$getB.top,
        scrollWrapperHeight = _scrollWrapperEl$getB.height;
      var parentHeight = window.innerHeight;
      var parentWidth = window.innerWidth;
      var parentTop = 0;
      var parentRight = parentWidth;
      var maxWidth = parseInt(width.value) - parseInt(widthCollapsed.value);
      if (relative.value) {
        var parent = sidebarRef.value.parentElement;
        parentHeight = parent.clientHeight;
        parentWidth = parent.clientWidth;
        parentTop = parent.getBoundingClientRect().top;
        parentRight = parent.getBoundingClientRect().right;
      }
      var rectWidth = rtl.value ? parentWidth - (parentRight - sidebarLeft) : parentRight - sidebarRight;
      maxWidth = rectWidth <= maxWidth ? rectWidth : maxWidth;
      var _window$getComputedSt = window.getComputedStyle(el),
        pl = _window$getComputedSt.paddingLeft,
        pr = _window$getComputedSt.paddingRight;
      var paddingLeft = parseInt(pl);
      var paddingRight = parseInt(pr);
      var absoluteTop = elTop - scrollWrapperTop;
      var absoluteBottom = wrapperBottom - elTop - (wrapperHeight - (scrollWrapperHeight + scrollWrapperOffsetTop));
      var maxHeight = parentHeight - (elBottom - parentTop);
      var parentVisibleHeight = Math.min(window.innerHeight, window.innerHeight - parentTop, parentHeight, parentHeight + parentTop);
      var maxVisible = parentVisibleHeight - (Math.max(elBottom, 0) - Math.max(parentTop, 0));
      var dropup = maxVisible < parentVisibleHeight * 0.25 ? absoluteBottom : 0;
      maxHeight = dropup ? elTop - parentTop : maxHeight;
      return {
        top: absoluteTop,
        height: elHeight,
        padding: [paddingLeft, paddingRight],
        maxWidth: maxWidth,
        maxHeight: maxHeight,
        dropup: dropup
      };
    };
    var unsetMobileItem = function unsetMobileItem() {
      var immediate = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      var delay = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 800;
      if (!getMobileItem.value) return;
      clearMobileItemTimeout();
      if (immediate) {
        updateMobileItem(null);
        return;
      }
      mobileItem.timeout = setTimeout(function () {
        updateMobileItem(null);
      }, delay);
    };
    var clearMobileItemTimeout = function clearMobileItemTimeout() {
      if (mobileItem.timeout) clearTimeout(mobileItem.timeout);
    };
    var updateMobileItem = function updateMobileItem(item) {
      mobileItem.item = item;
    };
    var updateMobileItemRect = function updateMobileItemRect(mobileItemRect) {
      Object.keys(mobileItem.rect).forEach(function (key) {
        mobileItem.rect[key] = mobileItemRect[key];
      });
    };
    var updateCurrentRoute = function updateCurrentRoute() {
      var route = window.location.pathname + window.location.search + window.location.hash;
      currentRoute.value = route;
    };
    var onItemClick = function onItemClick(event, item) {
      emits('item-click', event, item);
    };
    vue.provide('vsmProps', props);
    vue.provide('getSidebarRef', sidebarRef);
    vue.provide('getIsCollapsed', isCollapsed);
    vue.provide('getMobileItem', getMobileItem);
    vue.provide('getMobileItemRect', getMobileItemRect);
    vue.provide('getCurrentRoute', currentRoute);
    vue.provide('updateIsCollapsed', updateIsCollapsed);
    vue.provide('setMobileItem', setMobileItem);
    vue.provide('unsetMobileItem', unsetMobileItem);
    vue.provide('clearMobileItemTimeout', clearMobileItemTimeout);
    vue.provide('onRouteChange', updateCurrentRoute);
    vue.provide('emitItemClick', onItemClick);
    return {
      getSidebarRef: sidebarRef,
      getIsCollapsed: isCollapsed,
      getMobileItem: getMobileItem,
      getMobileItemRect: getMobileItemRect,
      getCurrentRoute: currentRoute,
      updateIsCollapsed: updateIsCollapsed,
      setMobileItem: setMobileItem,
      unsetMobileItem: unsetMobileItem,
      clearMobileItemTimeout: clearMobileItemTimeout,
      updateCurrentRoute: updateCurrentRoute,
      onItemClick: onItemClick
    };
  };
  var useSidebar = function useSidebar() {
    return {
      getSidebarProps: vue.inject('vsmProps'),
      getSidebarRef: vue.inject('getSidebarRef'),
      getIsCollapsed: vue.inject('getIsCollapsed'),
      getMobileItem: vue.inject('getMobileItem'),
      getMobileItemRect: vue.inject('getMobileItemRect'),
      getCurrentRoute: vue.inject('getCurrentRoute'),
      updateIsCollapsed: vue.inject('updateIsCollapsed'),
      setMobileItem: vue.inject('setMobileItem'),
      unsetMobileItem: vue.inject('unsetMobileItem'),
      clearMobileItemTimeout: vue.inject('clearMobileItemTimeout'),
      onRouteChange: vue.inject('onRouteChange'),
      emitItemClick: vue.inject('emitItemClick')
    };
  };

  function _defineProperty(e, r, t) {
    return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
      value: t,
      enumerable: !0,
      configurable: !0,
      writable: !0
    }) : e[r] = t, e;
  }
  function ownKeys(e, r) {
    var t = Object.keys(e);
    if (Object.getOwnPropertySymbols) {
      var o = Object.getOwnPropertySymbols(e);
      r && (o = o.filter(function (r) {
        return Object.getOwnPropertyDescriptor(e, r).enumerable;
      })), t.push.apply(t, o);
    }
    return t;
  }
  function _objectSpread2(e) {
    for (var r = 1; r < arguments.length; r++) {
      var t = null != arguments[r] ? arguments[r] : {};
      r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
        _defineProperty(e, r, t[r]);
      }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
        Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
      });
    }
    return e;
  }
  function _toPrimitive(t, r) {
    if ("object" != typeof t || !t) return t;
    var e = t[Symbol.toPrimitive];
    if (void 0 !== e) {
      var i = e.call(t, r || "default");
      if ("object" != typeof i) return i;
      throw new TypeError("@@toPrimitive must return a primitive value.");
    }
    return ("string" === r ? String : Number)(t);
  }
  function _toPropertyKey(t) {
    var i = _toPrimitive(t, "string");
    return "symbol" == typeof i ? i : i + "";
  }

  // Adapted from vue-router-next
  // See: https://github.com/vuejs/vue-router-next/blob/master/src/RouterLink.ts

  function activeRecordIndex(route, currentRoute) {
    var matched = route.matched;
    var length = matched.length;
    var routeMatched = matched[length - 1];
    var currentMatched = currentRoute.matched;
    if (!routeMatched || !currentMatched.length) return -1;
    var index = currentMatched.findIndex(isSameRouteRecord.bind(null, routeMatched));
    if (index > -1) return index;
    var parentRecordPath = getOriginalPath(matched[length - 2]);
    return length > 1 && getOriginalPath(routeMatched) === parentRecordPath && currentMatched[currentMatched.length - 1].path !== parentRecordPath ? currentMatched.findIndex(isSameRouteRecord.bind(null, matched[length - 2])) : index;
  }
  function isSameRouteLocationParams(a, b) {
    if (Object.keys(a).length !== Object.keys(b).length) return false;
    for (var key in a) {
      if (!isSameRouteLocationParamsValue(a[key], b[key])) return false;
    }
    return true;
  }
  function includesParams(outer, inner) {
    var _loop = function _loop() {
        var innerValue = inner[key];
        var outerValue = outer[key];
        if (typeof innerValue === 'string') {
          if (innerValue !== outerValue) return {
            v: false
          };
        } else {
          if (!Array.isArray(outerValue) || outerValue.length !== innerValue.length || innerValue.some(function (value, i) {
            return value !== outerValue[i];
          })) {
            return {
              v: false
            };
          }
        }
      },
      _ret;
    for (var key in inner) {
      _ret = _loop();
      if (_ret) return _ret.v;
    }
    return true;
  }
  function getOriginalPath(record) {
    return record ? record.aliasOf ? record.aliasOf.path : record.path : '';
  }
  function isSameRouteRecord(a, b) {
    return (a.aliasOf || a) === (b.aliasOf || b);
  }
  function isSameRouteLocationParamsValue(a, b) {
    return Array.isArray(a) ? isEquivalentArray(a, b) : Array.isArray(b) ? isEquivalentArray(b, a) : a === b;
  }
  function isEquivalentArray(a, b) {
    return Array.isArray(b) ? a.length === b.length && a.every(function (value, i) {
      return value === b[i];
    }) : a.length === 1 && a[0] === b;
  }

  function useItem(props, emits) {
    var router = vue.getCurrentInstance().appContext.config.globalProperties.$router;
    var _useSidebar = useSidebar(),
      sidebarProps = _useSidebar.getSidebarProps,
      isCollapsed = _useSidebar.getIsCollapsed,
      mobileItem = _useSidebar.getMobileItem,
      mobileItemRect = _useSidebar.getMobileItemRect,
      currentRoute = _useSidebar.getCurrentRoute,
      setMobileItem = _useSidebar.setMobileItem,
      unsetMobileItem = _useSidebar.unsetMobileItem,
      clearMobileItemTimeout = _useSidebar.clearMobileItemTimeout,
      emitItemClick = _useSidebar.emitItemClick;
    var emitScrollUpdate = vue.inject('emitScrollUpdate');
    var itemShow = vue.ref(false);
    var itemHover = vue.ref(false);
    var active = vue.computed(function () {
      return isLinkActive(props.item) || _isChildActive(props.item.child);
    });
    var exactActive = vue.computed(function () {
      return isLinkActive(props.item, true);
    });
    var isLinkActive = function isLinkActive(item, exact) {
      if (item.isActive && typeof item.isActive === 'function') {
        var isActive = item.isActive(item);
        if (typeof isActive === 'boolean') {
          return isActive;
        }
      }
      if (!item.href || item.external) return false;
      if (router) {
        var route = router.resolve(item.href);
        var routerCurrentRoute = router.currentRoute.value;
        var activeIndex = activeRecordIndex(route, routerCurrentRoute);
        if (exact || item.exact) {
          return activeIndex > -1 && activeIndex === routerCurrentRoute.matched.length - 1 && isSameRouteLocationParams(routerCurrentRoute.params, route.params);
        }
        return activeIndex > -1 && includesParams(routerCurrentRoute.params, route.params);
      } else {
        return item.href === currentRoute.value;
      }
    };
    var _isChildActive = function isChildActive(child) {
      if (!child) return false;
      return child.some(function (item) {
        return isLinkActive(item) || _isChildActive(item.child);
      });
    };
    var onLinkClick = function onLinkClick(event) {
      if (!props.item.href || props.item.disabled) {
        event.preventDefault();
        if (props.item.disabled) return;
      }
      emitMobileItem(event, event.currentTarget.parentElement);
      if (hasChild.value) {
        if (!props.item.href || active.value) {
          show.value = !show.value;
        }
      }
      emitItemClick(event, props.item);
    };
    var onMouseOver = function onMouseOver(event) {
      if (props.item.disabled) return;
      event.stopPropagation();
      itemHover.value = true;
    };
    var onMouseOut = function onMouseOut(event) {
      event.stopPropagation();
      itemHover.value = false;
    };
    var onMouseEnter = function onMouseEnter(event) {
      if (props.item.disabled) return;
      if (sidebarProps.disableHover) {
        if (isMobileItem.value && hasChild.value) {
          clearMobileItemTimeout();
        }
      } else {
        clearMobileItemTimeout();
        emitMobileItem(event, event.currentTarget);
      }
    };
    var onMouseLeave = function onMouseLeave() {
      if (sidebarProps.disableHover && !hasChild.value) return;
      if (isMobileItem.value) {
        unsetMobileItem(false, !sidebarProps.disableHover ? 300 : undefined);
      }
    };
    var emitMobileItem = function emitMobileItem(event, itemEl) {
      if (isMobileItem.value) return;
      if (isCollapsed.value) {
        setTimeout(function () {
          if (isFirstLevel.value) {
            if (!isMobileItem.value) {
              setMobileItem({
                item: props.item,
                itemEl: itemEl
              });
            }
          }
          if (event.type === 'click' && !hasChild.value) {
            unsetMobileItem(false, !isFirstLevel.value ? 300 : undefined);
          }
        }, 0);
      }
    };
    var onExpandEnter = function onExpandEnter(el) {
      el.style.height = el.scrollHeight + 'px';
    };
    var onExpandAfterEnter = function onExpandAfterEnter(el) {
      el.style.height = 'auto';
      if (!isCollapsed.value) {
        emitScrollUpdate();
      }
    };
    var onExpandBeforeLeave = function onExpandBeforeLeave(el) {
      if (isCollapsed.value && isFirstLevel.value) {
        el.style.display = 'none';
        return;
      }
      el.style.height = el.scrollHeight + 'px';
    };
    var onExpandAfterLeave = function onExpandAfterLeave() {
      if (!isCollapsed.value) {
        emitScrollUpdate();
      }
    };
    var show = vue.computed({
      get: function get() {
        if (!hasChild.value) return false;
        if (isCollapsed.value && isFirstLevel.value) return hover.value;
        if (sidebarProps.showChild) return true;
        if (sidebarProps.showOneChild && isFirstLevel.value || sidebarProps.showOneChild === 'deep') {
          return props.item.id === props.activeShow;
        } else {
          return itemShow.value;
        }
      },
      set: function set(show) {
        if (sidebarProps.showOneChild && isFirstLevel.value || sidebarProps.showOneChild === 'deep') {
          if (show) {
            emits('update-active-show', props.item.id);
          } else {
            emits('update-active-show', undefined);
          }
        }
        itemShow.value = show;
      }
    });
    var hover = vue.computed(function () {
      return isCollapsed.value && isFirstLevel.value ? isMobileItem.value : itemHover.value;
    });
    var isFirstLevel = vue.computed(function () {
      return props.level === 1;
    });
    var isHidden = vue.computed(function () {
      if (isCollapsed.value) {
        if (props.item.hidden && props.item.hiddenOnCollapse === undefined) {
          return true;
        } else {
          return props.item.hiddenOnCollapse === true;
        }
      } else {
        return props.item.hidden === true;
      }
    });
    var hasChild = vue.computed(function () {
      return !!(props.item.child && props.item.child.length > 0);
    });
    var linkClass = vue.computed(function () {
      return ['vsm--link', "vsm--link_level-".concat(props.level), {
        'vsm--link_mobile': isMobileItem.value,
        'vsm--link_hover': hover.value,
        'vsm--link_active': active.value,
        'vsm--link_disabled': props.item.disabled,
        'vsm--link_open': show.value
      }, props.item.class];
    });
    var linkAttrs = vue.computed(function () {
      var href = props.item.href ? props.item.href : '#';
      var target = props.item.external ? '_blank' : '_self';
      var tabindex = props.item.disabled ? -1 : null;
      var ariaCurrent = exactActive.value ? 'page' : null;
      var ariaExpanded = hasChild.value ? show.value : null;
      var ariaControls = ariaExpanded ? "vsm-".concat(props.item.id) : null;
      return _objectSpread2({
        href: href,
        target: target,
        tabindex: tabindex,
        'aria-current': ariaCurrent,
        'aria-expanded': ariaExpanded,
        'aria-controls': ariaControls
      }, props.item.attributes);
    });
    var childAttrs = vue.computed(function () {
      return {
        id: "vsm-".concat(props.item.id)
      };
    });
    var itemClass = vue.computed(function () {
      return ['vsm--item', {
        'vsm--item_mobile': isMobileItem.value
      }];
    });
    var isMobileItem = vue.computed(function () {
      var _mobileItem$value;
      return props.item.id === ((_mobileItem$value = mobileItem.value) === null || _mobileItem$value === void 0 ? void 0 : _mobileItem$value.id);
    });
    var mobileItemDropdownStyle = vue.computed(function () {
      return _objectSpread2(_objectSpread2({
        position: 'absolute',
        'max-height': "".concat(mobileItemRect.value.maxHeight, "px"),
        width: "".concat(mobileItemRect.value.maxWidth, "px"),
        'overflow-y': 'auto'
      }, !mobileItemRect.value.dropup ? {
        top: "".concat(mobileItemRect.value.top + mobileItemRect.value.height, "px")
      } : {
        bottom: "".concat(mobileItemRect.value.dropup, "px")
      }), !sidebarProps.rtl ? {
        left: sidebarProps.widthCollapsed
      } : {
        right: sidebarProps.widthCollapsed
      });
    });
    var mobileItemStyle = vue.computed(function () {
      return _objectSpread2(_objectSpread2({
        position: 'absolute',
        top: "".concat(mobileItemRect.value.top, "px")
      }, !sidebarProps.rtl ? {
        left: sidebarProps.widthCollapsed
      } : {
        right: sidebarProps.widthCollapsed
      }), {}, {
        width: "".concat(mobileItemRect.value.maxWidth, "px"),
        height: "".concat(mobileItemRect.value.height, "px"),
        'padding-left': "".concat(mobileItemRect.value.padding[0], "px"),
        'padding-right': "".concat(mobileItemRect.value.padding[1], "px"),
        'z-index': '20'
      });
    });
    var mobileItemBackgroundStyle = vue.computed(function () {
      return _objectSpread2(_objectSpread2({
        position: 'absolute',
        top: "".concat(mobileItemRect.value.top, "px")
      }, !sidebarProps.rtl ? {
        left: '0px'
      } : {
        right: '0px'
      }), {}, {
        width: "".concat(mobileItemRect.value.maxWidth + parseInt(sidebarProps.widthCollapsed), "px"),
        height: "".concat(mobileItemRect.value.height, "px"),
        'z-index': '10'
      });
    });
    vue.watch(function () {
      return active.value;
    }, function () {
      if (active.value) {
        show.value = true;
      }
    }, {
      immediate: true
    });
    return {
      active: active,
      exactActive: exactActive,
      show: show,
      hover: hover,
      isFirstLevel: isFirstLevel,
      isHidden: isHidden,
      hasChild: hasChild,
      linkClass: linkClass,
      linkAttrs: linkAttrs,
      childAttrs: childAttrs,
      itemClass: itemClass,
      isMobileItem: isMobileItem,
      mobileItemDropdownStyle: mobileItemDropdownStyle,
      mobileItemStyle: mobileItemStyle,
      mobileItemBackgroundStyle: mobileItemBackgroundStyle,
      onLinkClick: onLinkClick,
      onMouseOver: onMouseOver,
      onMouseOut: onMouseOut,
      onMouseEnter: onMouseEnter,
      onMouseLeave: onMouseLeave,
      onExpandEnter: onExpandEnter,
      onExpandAfterEnter: onExpandAfterEnter,
      onExpandBeforeLeave: onExpandBeforeLeave,
      onExpandAfterLeave: onExpandAfterLeave
    };
  }

  const _hoisted_1$2 = ["href", "onClick"];


  const __default__$5 = {
    compatConfig: {
      MODE: 3,
      inheritAttrs: false,
    },
  };

  var script$5 = /*@__PURE__*/Object.assign(__default__$5, {
    __name: 'SidebarMenuLink',
    props: {
    item: {
      type: Object,
      required: true,
    },
  },
    setup(__props) {

  const props = __props;

  const router = vue.getCurrentInstance().appContext.config.globalProperties.$router;

  const isHyperLink = vue.computed(() => {
    return !!(!props.item.href || props.item.external || !router)
  });

  return (_ctx, _cache) => {
    const _component_router_link = vue.resolveComponent("router-link");

    return (isHyperLink.value)
      ? (vue.openBlock(), vue.createElementBlock("a", vue.normalizeProps(vue.mergeProps({ key: 0 }, _ctx.$attrs)), [
          vue.renderSlot(_ctx.$slots, "default")
        ], 16 /* FULL_PROPS */))
      : (vue.openBlock(), vue.createBlock(_component_router_link, {
          key: 1,
          custom: "",
          to: _ctx.$attrs.href
        }, {
          default: vue.withCtx(({ href, navigate }) => [
            vue.createElementVNode("a", vue.mergeProps(_ctx.$attrs, {
              href: href,
              onClick: navigate
            }), [
              vue.renderSlot(_ctx.$slots, "default")
            ], 16 /* FULL_PROPS */, _hoisted_1$2)
          ]),
          _: 3 /* FORWARDED */
        }, 8 /* PROPS */, ["to"]))
  }
  }

  });

  script$5.__file = "src/components/SidebarMenuLink.vue";

  const __default__$4 = {
    compatConfig: { MODE: 3 },
  };

  var script$4 = /*@__PURE__*/Object.assign(__default__$4, {
    __name: 'SidebarMenuIcon',
    props: {
    icon: {
      type: [String, Object],
      default: '',
    },
  },
    setup(__props) {

  const props = __props;

  const attributes = vue.computed(() => {
    return {
      class: [
        'vsm--icon',
        typeof props.icon === 'object' ? props.icon.class : props.icon,
      ],
      'aria-hidden': true,
      ...props.icon.attributes,
    }
  });

  return (_ctx, _cache) => {
    return (typeof __props.icon === 'object' && __props.icon.text)
      ? (vue.openBlock(), vue.createBlock(vue.resolveDynamicComponent(__props.icon.element ? __props.icon.element : 'i'), vue.normalizeProps(vue.mergeProps({ key: 0 }, attributes.value)), {
          default: vue.withCtx(() => [
            vue.createTextVNode(vue.toDisplayString(__props.icon.text), 1 /* TEXT */)
          ]),
          _: 1 /* STABLE */
        }, 16 /* FULL_PROPS */))
      : (typeof __props.icon === 'object')
        ? (vue.openBlock(), vue.createBlock(vue.resolveDynamicComponent(__props.icon.element ? __props.icon.element : 'i'), vue.normalizeProps(vue.mergeProps({ key: 1 }, attributes.value)), null, 16 /* FULL_PROPS */))
        : (vue.openBlock(), vue.createElementBlock("i", vue.normalizeProps(vue.mergeProps({ key: 2 }, attributes.value)), null, 16 /* FULL_PROPS */))
  }
  }

  });

  script$4.__file = "src/components/SidebarMenuIcon.vue";

  const __default__$3 = {
    compatConfig: { MODE: 3 },
  };

  var script$3 = /*@__PURE__*/Object.assign(__default__$3, {
    __name: 'SidebarMenuBadge',
    props: {
    badge: {
      type: Object,
      default: () => {},
    },
  },
    setup(__props) {

  const props = __props;

  const attributes = vue.computed(() => {
    return {
      class: ['vsm--badge', props.badge.class],
      ...props.badge.attributes,
    }
  });

  return (_ctx, _cache) => {
    return (__props.badge.text)
      ? (vue.openBlock(), vue.createBlock(vue.resolveDynamicComponent(__props.badge.element ? __props.badge.element : 'span'), vue.normalizeProps(vue.mergeProps({ key: 0 }, attributes.value)), {
          default: vue.withCtx(() => [
            vue.createTextVNode(vue.toDisplayString(__props.badge.text), 1 /* TEXT */)
          ]),
          _: 1 /* STABLE */
        }, 16 /* FULL_PROPS */))
      : (vue.openBlock(), vue.createBlock(vue.resolveDynamicComponent(__props.badge.element ? __props.badge.element : 'span'), vue.normalizeProps(vue.mergeProps({ key: 1 }, attributes.value)), null, 16 /* FULL_PROPS */))
  }
  }

  });

  script$3.__file = "src/components/SidebarMenuBadge.vue";

  const _hoisted_1$1 = { key: 0 };
  const _hoisted_2$1 = { class: "vsm--dropdown" };


  const __default__$2 = {
    compatConfig: { MODE: 3 },
  };

  var script$2 = /*@__PURE__*/Object.assign(__default__$2, {
    __name: 'SidebarMenuItem',
    props: {
    item: {
      type: Object,
      required: true,
    },
    level: {
      type: Number,
      default: 1,
    },
    activeShow: {
      type: String,
      default: undefined,
    },
  },
    emits: ['update-active-show'],
    setup(__props, { emit: __emit }) {

  const props = __props;

  const emits = __emit;

  const { getSidebarProps, getIsCollapsed: isCollapsed } = useSidebar();
  const { linkComponentName } = vue.toRefs(getSidebarProps);
  const subActiveShow = vue.ref(undefined);

  const updateActiveShow = (id) => {
    subActiveShow.value = id;
  };

  const {
    show,
    hover,
    isFirstLevel,
    isHidden,
    hasChild,
    linkClass,
    linkAttrs,
    childAttrs,
    itemClass,
    isMobileItem,
    mobileItemStyle,
    mobileItemDropdownStyle,
    mobileItemBackgroundStyle,
    onLinkClick,
    onMouseOver,
    onMouseOut,
    onMouseEnter,
    onMouseLeave,
    onExpandEnter,
    onExpandAfterEnter,
    onExpandBeforeLeave,
    onExpandAfterLeave,
  } = useItem(props, emits);

  return (_ctx, _cache) => {
    const _component_sidebar_menu_item = vue.resolveComponent("sidebar-menu-item", true);

    return (__props.item.component && !vue.unref(isHidden))
      ? (vue.openBlock(), vue.createElementBlock("li", _hoisted_1$1, [
          (vue.openBlock(), vue.createBlock(vue.resolveDynamicComponent(__props.item.component), vue.normalizeProps(vue.guardReactiveProps(__props.item.props)), null, 16 /* FULL_PROPS */))
        ]))
      : (__props.item.header && !vue.unref(isHidden))
        ? (vue.openBlock(), vue.createElementBlock("li", vue.mergeProps({
            key: 1,
            class: ['vsm--header', __props.item.class]
          }, __props.item.attributes), vue.toDisplayString(__props.item.header), 17 /* TEXT, FULL_PROPS */))
        : (!vue.unref(isHidden))
          ? (vue.openBlock(), vue.createElementBlock("li", vue.mergeProps({
              key: 2,
              class: vue.unref(itemClass),
              onMouseover: _cache[0] || (_cache[0] = (...args) => (vue.unref(onMouseOver) && vue.unref(onMouseOver)(...args))),
              onMouseout: _cache[1] || (_cache[1] = (...args) => (vue.unref(onMouseOut) && vue.unref(onMouseOut)(...args)))
            }, vue.toHandlers(
        vue.unref(isCollapsed) && vue.unref(isFirstLevel)
          ? { mouseenter: vue.unref(onMouseEnter), mouseleave: vue.unref(onMouseLeave) }
          : {}
      , true)), [
              (vue.openBlock(), vue.createBlock(vue.resolveDynamicComponent(vue.unref(linkComponentName) ? vue.unref(linkComponentName) : script$5), vue.mergeProps({
                item: __props.item,
                class: vue.unref(linkClass)
              }, vue.unref(linkAttrs), { onClick: vue.unref(onLinkClick) }), {
                default: vue.withCtx(() => [
                  (vue.unref(isCollapsed) && vue.unref(isFirstLevel))
                    ? (vue.openBlock(), vue.createBlock(vue.Transition, {
                        key: 0,
                        name: "slide-animation"
                      }, {
                        default: vue.withCtx(() => [
                          (vue.unref(hover))
                            ? (vue.openBlock(), vue.createElementBlock("div", {
                                key: 0,
                                class: "vsm--mobile-bg",
                                style: vue.normalizeStyle(vue.unref(mobileItemBackgroundStyle))
                              }, null, 4 /* STYLE */))
                            : vue.createCommentVNode("v-if", true)
                        ]),
                        _: 1 /* STABLE */
                      }))
                    : vue.createCommentVNode("v-if", true),
                  (__props.item.icon)
                    ? (vue.openBlock(), vue.createBlock(script$4, {
                        key: 1,
                        icon: __props.item.icon
                      }, null, 8 /* PROPS */, ["icon"]))
                    : vue.createCommentVNode("v-if", true),
                  vue.createElementVNode("div", {
                    class: vue.normalizeClass([
            'vsm--title',
            vue.unref(isCollapsed) && vue.unref(isFirstLevel) && !vue.unref(isMobileItem) && 'vsm--title_hidden',
          ]),
                    style: vue.normalizeStyle(vue.unref(isMobileItem) && vue.unref(mobileItemStyle))
                  }, [
                    vue.createElementVNode("span", null, vue.toDisplayString(__props.item.title), 1 /* TEXT */),
                    (__props.item.badge)
                      ? (vue.openBlock(), vue.createBlock(script$3, {
                          key: 0,
                          badge: __props.item.badge
                        }, null, 8 /* PROPS */, ["badge"]))
                      : vue.createCommentVNode("v-if", true),
                    (vue.unref(hasChild))
                      ? (vue.openBlock(), vue.createElementBlock("div", {
                          key: 1,
                          class: vue.normalizeClass(['vsm--arrow', { 'vsm--arrow_open': vue.unref(show) }])
                        }, [
                          vue.renderSlot(_ctx.$slots, "dropdown-icon", vue.normalizeProps(vue.guardReactiveProps({ isOpen: vue.unref(show) })))
                        ], 2 /* CLASS */))
                      : vue.createCommentVNode("v-if", true)
                  ], 6 /* CLASS, STYLE */)
                ]),
                _: 3 /* FORWARDED */
              }, 16 /* FULL_PROPS */, ["item", "class", "onClick"])),
              (vue.unref(hasChild))
                ? (vue.openBlock(), vue.createBlock(vue.Transition, {
                    key: 0,
                    appear: vue.unref(isMobileItem),
                    name: "expand",
                    onEnter: vue.unref(onExpandEnter),
                    onAfterEnter: vue.unref(onExpandAfterEnter),
                    onBeforeLeave: vue.unref(onExpandBeforeLeave),
                    onAfterLeave: vue.unref(onExpandAfterLeave)
                  }, {
                    default: vue.withCtx(() => [
                      (vue.unref(show))
                        ? (vue.openBlock(), vue.createElementBlock("div", vue.mergeProps({
                            key: 0,
                            class: ['vsm--child', vue.unref(isMobileItem) && 'vsm--child_mobile'],
                            style: vue.unref(isMobileItem) && vue.unref(mobileItemDropdownStyle)
                          }, vue.unref(childAttrs)), [
                            vue.createElementVNode("ul", _hoisted_2$1, [
                              (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(__props.item.child, (subItem) => {
                                return (vue.openBlock(), vue.createBlock(_component_sidebar_menu_item, {
                                  key: subItem.id,
                                  item: subItem,
                                  level: __props.level + 1,
                                  "active-show": subActiveShow.value,
                                  onUpdateActiveShow: updateActiveShow
                                }, {
                                  "dropdown-icon": vue.withCtx(({ isOpen }) => [
                                    vue.renderSlot(_ctx.$slots, "dropdown-icon", vue.mergeProps({ ref_for: true }, { isOpen }))
                                  ]),
                                  _: 2 /* DYNAMIC */
                                }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["item", "level", "active-show"]))
                              }), 128 /* KEYED_FRAGMENT */))
                            ])
                          ], 16 /* FULL_PROPS */))
                        : vue.createCommentVNode("v-if", true)
                    ]),
                    _: 3 /* FORWARDED */
                  }, 8 /* PROPS */, ["appear", "onEnter", "onAfterEnter", "onBeforeLeave", "onAfterLeave"]))
                : vue.createCommentVNode("v-if", true)
            ], 16 /* FULL_PROPS */))
          : vue.createCommentVNode("v-if", true)
  }
  }

  });

  script$2.__file = "src/components/SidebarMenuItem.vue";

  const __default__$1 = {
    compatConfig: { MODE: 3 },
  };

  var script$1 = /*@__PURE__*/Object.assign(__default__$1, {
    __name: 'SidebarMenuScroll',
    setup(__props) {

  const { getIsCollapsed: isCollapsed } = useSidebar();

  const scrollRef = vue.ref(null);
  const scrollBarRef = vue.ref(null);
  const scrollThumbRef = vue.ref(null);

  let cursorY = 0;
  let cursorDown = false;

  const visible = vue.ref(false);

  const onScrollUpdate = () => {
    if (!scrollRef.value) return
    vue.nextTick(() => {
      updateThumb();
    });
  };

  const onScroll = () => {
    requestAnimationFrame(onScrollUpdate);
  };

  const onClick = (e) => {
    const offset = Math.abs(
      scrollBarRef.value.getBoundingClientRect().y - e.clientY
    );
    const thumbHalf = scrollThumbRef.value.offsetHeight / 2;
    updateScrollTop(offset - thumbHalf);
  };

  const onMouseDown = (e) => {
    e.stopImmediatePropagation();
    cursorDown = true;
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
    cursorY =
      scrollThumbRef.value.offsetHeight -
      (e.clientY - scrollThumbRef.value.getBoundingClientRect().y);
  };

  const onMouseMove = (e) => {
    if (!cursorDown) return
    const offset = e.clientY - scrollBarRef.value.getBoundingClientRect().y;
    const thumbClickPosition = scrollThumbRef.value.offsetHeight - cursorY;
    visible.value = true;
    updateScrollTop(offset - thumbClickPosition);
  };

  const onMouseUp = (e) => {
    cursorDown = false;
    cursorY = 0;
    visible.value = false;
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
  };

  const onMouseIn = (e) => {
    visible.value = true;
  };

  const onMouseLeave = (e) => {
    visible.value = false;
  };

  const updateThumb = () => {
    const heightPerc =
      (scrollRef.value.clientHeight * 100) / scrollRef.value.scrollHeight;
    const thumbHeightPerc = heightPerc < 100 ? heightPerc : 0;
    const thumbYPerc =
      (scrollRef.value.scrollTop * 100) / scrollRef.value.clientHeight || 0;

    scrollThumbRef.value.style.height = `${thumbHeightPerc}%`;
    scrollThumbRef.value.style.transform = `translateY(${thumbYPerc}%)`;
  };

  const updateScrollTop = (y) => {
    const scrollPerc = (y * 100) / scrollBarRef.value.offsetHeight;
    scrollRef.value.scrollTop = (scrollPerc * scrollRef.value.scrollHeight) / 100;
  };

  vue.watch(
    () => isCollapsed.value,
    () => {
      onScrollUpdate();
    }
  );

  vue.onMounted(() => {
    onScrollUpdate();
    window.addEventListener('resize', onScrollUpdate);
  });
  vue.onUnmounted(() => {
    window.removeEventListener('resize', onScrollUpdate);
  });

  vue.provide('emitScrollUpdate', onScrollUpdate);

  return (_ctx, _cache) => {
    return (vue.openBlock(), vue.createElementBlock("div", {
      class: "vsm--scroll-wrapper",
      onMousemove: onMouseIn,
      onMouseleave: onMouseLeave
    }, [
      vue.createElementVNode("div", {
        ref_key: "scrollRef",
        ref: scrollRef,
        class: "vsm--scroll",
        onScroll: onScroll
      }, [
        vue.renderSlot(_ctx.$slots, "default")
      ], 544 /* NEED_HYDRATION, NEED_PATCH */),
      vue.createVNode(vue.Transition, { persisted: "" }, {
        default: vue.withCtx(() => [
          vue.withDirectives(vue.createElementVNode("div", {
            ref_key: "scrollBarRef",
            ref: scrollBarRef,
            class: "vsm--scroll-bar",
            onMousedown: onClick
          }, [
            vue.createElementVNode("div", {
              ref_key: "scrollThumbRef",
              ref: scrollThumbRef,
              class: "vsm--scroll-thumb",
              onMousedown: onMouseDown
            }, null, 544 /* NEED_HYDRATION, NEED_PATCH */)
          ], 544 /* NEED_HYDRATION, NEED_PATCH */), [
            [vue.vShow, visible.value]
          ])
        ]),
        _: 1 /* STABLE */
      })
    ], 32 /* NEED_HYDRATION */))
  }
  }

  });

  script$1.__scopeId = "data-v-402f9588";
  script$1.__file = "src/components/SidebarMenuScroll.vue";

  const _hoisted_1 = { class: "vsm--wrapper" };
  const _hoisted_2 = ["aria-label"];


  const __default__ = {
    compatConfig: { MODE: 3 },
  };

  var script = /*@__PURE__*/Object.assign(__default__, {
    __name: 'SidebarMenu',
    props: {
    menu: {
      type: Array,
      required: true,
    },
    collapsed: {
      type: Boolean,
      default: false,
    },
    width: {
      type: String,
      default: '290px',
    },
    widthCollapsed: {
      type: String,
      default: '65px',
    },
    showChild: {
      type: Boolean,
      default: false,
    },
    theme: {
      type: String,
      default: undefined,
      validator: (value) => ['', 'white-theme'].includes(value),
    },
    showOneChild: {
      type: [Boolean, String],
      default: false,
      validator(value) {
        if (typeof value === 'string') {
          return ['deep'].includes(value)
        } else {
          return typeof value === 'boolean'
        }
      },
    },
    rtl: {
      type: Boolean,
      default: false,
    },
    relative: {
      type: Boolean,
      default: false,
    },
    hideToggle: {
      type: Boolean,
      default: false,
    },
    disableHover: {
      type: Boolean,
      default: false,
    },
    linkComponentName: {
      type: String,
      default: undefined,
    },
  },
    emits: {
    'item-click'(event, item) {
      return !!(event && item)
    },
    'update:collapsed'(collapsed) {
      return !!(typeof collapsed === 'boolean')
    },
  },
    setup(__props, { expose: __expose, emit: __emit }) {

  const props = __props;

  const emits = __emit;

  const {
    getSidebarRef: sidebarMenuRef,
    getIsCollapsed: isCollapsed,
    updateIsCollapsed,
    unsetMobileItem,
    updateCurrentRoute,
  } = initSidebar(props, emits);

  const activeShow = vue.ref(undefined);

  const computedMenu = vue.computed(() => {
    let id = 0;
    function transformItems(items) {
      function randomId() {
        return `${Date.now() + '' + id++}`
      }
      return items.map((item) => {
        return {
          id: randomId(),
          ...item,
          ...(item.child && { child: transformItems(item.child) }),
        }
      })
    }
    return transformItems(props.menu)
  });

  const sidebarWidth = vue.computed(() => {
    return isCollapsed.value ? props.widthCollapsed : props.width
  });

  const sidebarClass = vue.computed(() => {
    return [
      'v-sidebar-menu',
      !isCollapsed.value ? 'vsm_expanded' : 'vsm_collapsed',
      props.theme && `vsm_${props.theme}`,
      props.rtl && 'vsm_rtl',
      props.relative && 'vsm_relative',
    ]
  });

  const updateActiveShow = (id) => {
    activeShow.value = id;
  };

  const onToggleClick = () => {
    unsetMobileItem();
    updateIsCollapsed(!isCollapsed.value);
    emits('update:collapsed', isCollapsed.value);
  };

  vue.watch(
    () => props.collapsed,
    (currentCollapsed) => {
      unsetMobileItem();
      updateIsCollapsed(currentCollapsed);
    }
  );

  const router = vue.getCurrentInstance().appContext.config.globalProperties.$router;
  if (!router) {
    vue.onMounted(() => {
      updateCurrentRoute();
      window.addEventListener('hashchange', updateCurrentRoute);
    });
    vue.onUnmounted(() => {
      window.removeEventListener('hashchange', updateCurrentRoute);
    });
  }

  __expose({
    onRouteChange: updateCurrentRoute,
  });

  return (_ctx, _cache) => {
    return (vue.openBlock(), vue.createElementBlock("div", {
      ref_key: "sidebarMenuRef",
      ref: sidebarMenuRef,
      class: vue.normalizeClass([sidebarClass.value]),
      style: vue.normalizeStyle({ 'max-width': sidebarWidth.value })
    }, [
      vue.createElementVNode("div", _hoisted_1, [
        vue.renderSlot(_ctx.$slots, "header"),
        vue.createVNode(script$1, null, {
          default: vue.withCtx(() => [
            vue.createElementVNode("ul", {
              class: "vsm--menu",
              style: vue.normalizeStyle({ width: sidebarWidth.value })
            }, [
              (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(computedMenu.value, (item) => {
                return (vue.openBlock(), vue.createBlock(script$2, {
                  key: item.id,
                  item: item,
                  "active-show": activeShow.value,
                  onUpdateActiveShow: updateActiveShow
                }, {
                  "dropdown-icon": vue.withCtx(({ isOpen }) => [
                    vue.renderSlot(_ctx.$slots, "dropdown-icon", vue.mergeProps({ ref_for: true }, { isOpen }), () => [
                      _cache[0] || (_cache[0] = vue.createElementVNode("span", { class: "vsm--arrow_default" }, null, -1 /* HOISTED */))
                    ])
                  ]),
                  _: 2 /* DYNAMIC */
                }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["item", "active-show"]))
              }), 128 /* KEYED_FRAGMENT */))
            ], 4 /* STYLE */)
          ]),
          _: 3 /* FORWARDED */
        }),
        vue.renderSlot(_ctx.$slots, "footer")
      ]),
      (!__props.hideToggle)
        ? (vue.openBlock(), vue.createElementBlock("button", {
            key: 0,
            class: "vsm--toggle-btn",
            "aria-label": __props.collapsed ? 'Expand sidebar' : 'Collapse sidebar',
            onClick: onToggleClick
          }, [
            vue.renderSlot(_ctx.$slots, "toggle-icon", {}, () => [
              _cache[1] || (_cache[1] = vue.createElementVNode("span", { class: "vsm--toggle-btn_default" }, null, -1 /* HOISTED */))
            ])
          ], 8 /* PROPS */, _hoisted_2))
        : vue.createCommentVNode("v-if", true)
    ], 6 /* CLASS, STYLE */))
  }
  }

  });

  script.__file = "src/components/SidebarMenu.vue";

  var index = {
    install: function install(Vue) {
      Vue.component('SidebarMenu', script);
    }
  };

  exports.SidebarMenu = script;
  exports.default = index;

  Object.defineProperty(exports, '__esModule', { value: true });

}));
//# sourceMappingURL=vue-sidebar-menu.js.map
