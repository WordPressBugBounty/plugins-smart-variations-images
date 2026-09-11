(function() {
  "use strict";
  var define_global_process_env_default = {};
  /*!
   * Vue.js v2.7.16
   * (c) 2014-2023 Evan You
   * Released under the MIT License.
   */
  var emptyObject = Object.freeze({});
  var isArray = Array.isArray;
  function isUndef(v2) {
    return v2 === void 0 || v2 === null;
  }
  function isDef(v2) {
    return v2 !== void 0 && v2 !== null;
  }
  function isTrue(v2) {
    return v2 === true;
  }
  function isFalse(v2) {
    return v2 === false;
  }
  function isPrimitive(value) {
    return typeof value === "string" || typeof value === "number" || // $flow-disable-line
    typeof value === "symbol" || typeof value === "boolean";
  }
  function isFunction(value) {
    return typeof value === "function";
  }
  function isObject(obj) {
    return obj !== null && typeof obj === "object";
  }
  var _toString = Object.prototype.toString;
  function isPlainObject(obj) {
    return _toString.call(obj) === "[object Object]";
  }
  function isRegExp(v2) {
    return _toString.call(v2) === "[object RegExp]";
  }
  function isValidArrayIndex(val) {
    var n = parseFloat(String(val));
    return n >= 0 && Math.floor(n) === n && isFinite(val);
  }
  function isPromise(val) {
    return isDef(val) && typeof val.then === "function" && typeof val.catch === "function";
  }
  function toString(val) {
    return val == null ? "" : Array.isArray(val) || isPlainObject(val) && val.toString === _toString ? JSON.stringify(val, replacer, 2) : String(val);
  }
  function replacer(_key, val) {
    if (val && val.__v_isRef) {
      return val.value;
    }
    return val;
  }
  function toNumber(val) {
    var n = parseFloat(val);
    return isNaN(n) ? val : n;
  }
  function makeMap(str, expectsLowerCase) {
    var map = /* @__PURE__ */ Object.create(null);
    var list = str.split(",");
    for (var i = 0; i < list.length; i++) {
      map[list[i]] = true;
    }
    return expectsLowerCase ? function(val) {
      return map[val.toLowerCase()];
    } : function(val) {
      return map[val];
    };
  }
  makeMap("slot,component", true);
  var isReservedAttribute = makeMap("key,ref,slot,slot-scope,is");
  function remove$2(arr, item) {
    var len = arr.length;
    if (len) {
      if (item === arr[len - 1]) {
        arr.length = len - 1;
        return;
      }
      var index2 = arr.indexOf(item);
      if (index2 > -1) {
        return arr.splice(index2, 1);
      }
    }
  }
  var hasOwnProperty = Object.prototype.hasOwnProperty;
  function hasOwn(obj, key) {
    return hasOwnProperty.call(obj, key);
  }
  function cached(fn) {
    var cache = /* @__PURE__ */ Object.create(null);
    return function cachedFn(str) {
      var hit = cache[str];
      return hit || (cache[str] = fn(str));
    };
  }
  var camelizeRE = /-(\w)/g;
  var camelize = cached(function(str) {
    return str.replace(camelizeRE, function(_, c) {
      return c ? c.toUpperCase() : "";
    });
  });
  var capitalize = cached(function(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  });
  var hyphenateRE = /\B([A-Z])/g;
  var hyphenate = cached(function(str) {
    return str.replace(hyphenateRE, "-$1").toLowerCase();
  });
  function polyfillBind(fn, ctx) {
    function boundFn(a) {
      var l = arguments.length;
      return l ? l > 1 ? fn.apply(ctx, arguments) : fn.call(ctx, a) : fn.call(ctx);
    }
    boundFn._length = fn.length;
    return boundFn;
  }
  function nativeBind(fn, ctx) {
    return fn.bind(ctx);
  }
  var bind = Function.prototype.bind ? nativeBind : polyfillBind;
  function toArray(list, start) {
    start = start || 0;
    var i = list.length - start;
    var ret = new Array(i);
    while (i--) {
      ret[i] = list[i + start];
    }
    return ret;
  }
  function extend(to, _from) {
    for (var key in _from) {
      to[key] = _from[key];
    }
    return to;
  }
  function toObject(arr) {
    var res = {};
    for (var i = 0; i < arr.length; i++) {
      if (arr[i]) {
        extend(res, arr[i]);
      }
    }
    return res;
  }
  function noop(a, b, c) {
  }
  var no = function(a, b, c) {
    return false;
  };
  var identity = function(_) {
    return _;
  };
  function looseEqual(a, b) {
    if (a === b)
      return true;
    var isObjectA = isObject(a);
    var isObjectB = isObject(b);
    if (isObjectA && isObjectB) {
      try {
        var isArrayA = Array.isArray(a);
        var isArrayB = Array.isArray(b);
        if (isArrayA && isArrayB) {
          return a.length === b.length && a.every(function(e, i) {
            return looseEqual(e, b[i]);
          });
        } else if (a instanceof Date && b instanceof Date) {
          return a.getTime() === b.getTime();
        } else if (!isArrayA && !isArrayB) {
          var keysA = Object.keys(a);
          var keysB = Object.keys(b);
          return keysA.length === keysB.length && keysA.every(function(key) {
            return looseEqual(a[key], b[key]);
          });
        } else {
          return false;
        }
      } catch (e) {
        return false;
      }
    } else if (!isObjectA && !isObjectB) {
      return String(a) === String(b);
    } else {
      return false;
    }
  }
  function looseIndexOf(arr, val) {
    for (var i = 0; i < arr.length; i++) {
      if (looseEqual(arr[i], val))
        return i;
    }
    return -1;
  }
  function once(fn) {
    var called = false;
    return function() {
      if (!called) {
        called = true;
        fn.apply(this, arguments);
      }
    };
  }
  function hasChanged(x, y) {
    if (x === y) {
      return x === 0 && 1 / x !== 1 / y;
    } else {
      return x === x || y === y;
    }
  }
  var SSR_ATTR = "data-server-rendered";
  var ASSET_TYPES = ["component", "directive", "filter"];
  var LIFECYCLE_HOOKS = [
    "beforeCreate",
    "created",
    "beforeMount",
    "mounted",
    "beforeUpdate",
    "updated",
    "beforeDestroy",
    "destroyed",
    "activated",
    "deactivated",
    "errorCaptured",
    "serverPrefetch",
    "renderTracked",
    "renderTriggered"
  ];
  var config = {
    /**
     * Option merge strategies (used in core/util/options)
     */
    // $flow-disable-line
    optionMergeStrategies: /* @__PURE__ */ Object.create(null),
    /**
     * Whether to suppress warnings.
     */
    silent: false,
    /**
     * Show production mode tip message on boot?
     */
    productionTip: false,
    /**
     * Whether to enable devtools
     */
    devtools: false,
    /**
     * Whether to record perf
     */
    performance: false,
    /**
     * Error handler for watcher errors
     */
    errorHandler: null,
    /**
     * Warn handler for watcher warns
     */
    warnHandler: null,
    /**
     * Ignore certain custom elements
     */
    ignoredElements: [],
    /**
     * Custom user key aliases for v-on
     */
    // $flow-disable-line
    keyCodes: /* @__PURE__ */ Object.create(null),
    /**
     * Check if a tag is reserved so that it cannot be registered as a
     * component. This is platform-dependent and may be overwritten.
     */
    isReservedTag: no,
    /**
     * Check if an attribute is reserved so that it cannot be used as a component
     * prop. This is platform-dependent and may be overwritten.
     */
    isReservedAttr: no,
    /**
     * Check if a tag is an unknown element.
     * Platform-dependent.
     */
    isUnknownElement: no,
    /**
     * Get the namespace of an element
     */
    getTagNamespace: noop,
    /**
     * Parse the real tag name for the specific platform.
     */
    parsePlatformTagName: identity,
    /**
     * Check if an attribute must be bound using property, e.g. value
     * Platform-dependent.
     */
    mustUseProp: no,
    /**
     * Perform updates asynchronously. Intended to be used by Vue Test Utils
     * This will significantly reduce performance if set to false.
     */
    async: true,
    /**
     * Exposed for legacy reasons
     */
    _lifecycleHooks: LIFECYCLE_HOOKS
  };
  var unicodeRegExp = /a-zA-Z\u00B7\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u037D\u037F-\u1FFF\u200C-\u200D\u203F-\u2040\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD/;
  function isReserved(str) {
    var c = (str + "").charCodeAt(0);
    return c === 36 || c === 95;
  }
  function def(obj, key, val, enumerable) {
    Object.defineProperty(obj, key, {
      value: val,
      enumerable: false,
      writable: true,
      configurable: true
    });
  }
  var bailRE = new RegExp("[^".concat(unicodeRegExp.source, ".$_\\d]"));
  function parsePath(path) {
    if (bailRE.test(path)) {
      return;
    }
    var segments = path.split(".");
    return function(obj) {
      for (var i = 0; i < segments.length; i++) {
        if (!obj)
          return;
        obj = obj[segments[i]];
      }
      return obj;
    };
  }
  var hasProto = "__proto__" in {};
  var inBrowser = typeof window !== "undefined";
  var UA = inBrowser && window.navigator.userAgent.toLowerCase();
  var isIE = UA && /msie|trident/.test(UA);
  var isIE9 = UA && UA.indexOf("msie 9.0") > 0;
  var isEdge = UA && UA.indexOf("edge/") > 0;
  UA && UA.indexOf("android") > 0;
  var isIOS = UA && /iphone|ipad|ipod|ios/.test(UA);
  var isFF = UA && UA.match(/firefox\/(\d+)/);
  var nativeWatch = {}.watch;
  var supportsPassive = false;
  if (inBrowser) {
    try {
      var opts = {};
      Object.defineProperty(opts, "passive", {
        get: function() {
          supportsPassive = true;
        }
      });
      window.addEventListener("test-passive", null, opts);
    } catch (e) {
    }
  }
  var _isServer;
  var isServerRendering = function() {
    if (_isServer === void 0) {
      if (!inBrowser && typeof global !== "undefined") {
        _isServer = global["process"] && define_global_process_env_default.VUE_ENV === "server";
      } else {
        _isServer = false;
      }
    }
    return _isServer;
  };
  var devtools = inBrowser && window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
  function isNative(Ctor) {
    return typeof Ctor === "function" && /native code/.test(Ctor.toString());
  }
  var hasSymbol = typeof Symbol !== "undefined" && isNative(Symbol) && typeof Reflect !== "undefined" && isNative(Reflect.ownKeys);
  var _Set;
  if (typeof Set !== "undefined" && isNative(Set)) {
    _Set = Set;
  } else {
    _Set = /** @class */
    function() {
      function Set2() {
        this.set = /* @__PURE__ */ Object.create(null);
      }
      Set2.prototype.has = function(key) {
        return this.set[key] === true;
      };
      Set2.prototype.add = function(key) {
        this.set[key] = true;
      };
      Set2.prototype.clear = function() {
        this.set = /* @__PURE__ */ Object.create(null);
      };
      return Set2;
    }();
  }
  var currentInstance = null;
  function setCurrentInstance(vm) {
    if (vm === void 0) {
      vm = null;
    }
    if (!vm)
      currentInstance && currentInstance._scope.off();
    currentInstance = vm;
    vm && vm._scope.on();
  }
  var VNode = (
    /** @class */
    function() {
      function VNode2(tag, data, children, text, elm, context, componentOptions, asyncFactory) {
        this.tag = tag;
        this.data = data;
        this.children = children;
        this.text = text;
        this.elm = elm;
        this.ns = void 0;
        this.context = context;
        this.fnContext = void 0;
        this.fnOptions = void 0;
        this.fnScopeId = void 0;
        this.key = data && data.key;
        this.componentOptions = componentOptions;
        this.componentInstance = void 0;
        this.parent = void 0;
        this.raw = false;
        this.isStatic = false;
        this.isRootInsert = true;
        this.isComment = false;
        this.isCloned = false;
        this.isOnce = false;
        this.asyncFactory = asyncFactory;
        this.asyncMeta = void 0;
        this.isAsyncPlaceholder = false;
      }
      Object.defineProperty(VNode2.prototype, "child", {
        // DEPRECATED: alias for componentInstance for backwards compat.
        /* istanbul ignore next */
        get: function() {
          return this.componentInstance;
        },
        enumerable: false,
        configurable: true
      });
      return VNode2;
    }()
  );
  var createEmptyVNode = function(text) {
    if (text === void 0) {
      text = "";
    }
    var node = new VNode();
    node.text = text;
    node.isComment = true;
    return node;
  };
  function createTextVNode(val) {
    return new VNode(void 0, void 0, void 0, String(val));
  }
  function cloneVNode(vnode) {
    var cloned = new VNode(
      vnode.tag,
      vnode.data,
      // #7975
      // clone children array to avoid mutating original in case of cloning
      // a child.
      vnode.children && vnode.children.slice(),
      vnode.text,
      vnode.elm,
      vnode.context,
      vnode.componentOptions,
      vnode.asyncFactory
    );
    cloned.ns = vnode.ns;
    cloned.isStatic = vnode.isStatic;
    cloned.key = vnode.key;
    cloned.isComment = vnode.isComment;
    cloned.fnContext = vnode.fnContext;
    cloned.fnOptions = vnode.fnOptions;
    cloned.fnScopeId = vnode.fnScopeId;
    cloned.asyncMeta = vnode.asyncMeta;
    cloned.isCloned = true;
    return cloned;
  }
  typeof SuppressedError === "function" ? SuppressedError : function(error, suppressed, message) {
    var e = new Error(message);
    return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
  };
  var uid$2 = 0;
  var pendingCleanupDeps = [];
  var cleanupDeps = function() {
    for (var i = 0; i < pendingCleanupDeps.length; i++) {
      var dep = pendingCleanupDeps[i];
      dep.subs = dep.subs.filter(function(s) {
        return s;
      });
      dep._pending = false;
    }
    pendingCleanupDeps.length = 0;
  };
  var Dep = (
    /** @class */
    function() {
      function Dep2() {
        this._pending = false;
        this.id = uid$2++;
        this.subs = [];
      }
      Dep2.prototype.addSub = function(sub) {
        this.subs.push(sub);
      };
      Dep2.prototype.removeSub = function(sub) {
        this.subs[this.subs.indexOf(sub)] = null;
        if (!this._pending) {
          this._pending = true;
          pendingCleanupDeps.push(this);
        }
      };
      Dep2.prototype.depend = function(info) {
        if (Dep2.target) {
          Dep2.target.addDep(this);
        }
      };
      Dep2.prototype.notify = function(info) {
        var subs = this.subs.filter(function(s) {
          return s;
        });
        for (var i = 0, l = subs.length; i < l; i++) {
          var sub = subs[i];
          sub.update();
        }
      };
      return Dep2;
    }()
  );
  Dep.target = null;
  var targetStack = [];
  function pushTarget(target2) {
    targetStack.push(target2);
    Dep.target = target2;
  }
  function popTarget() {
    targetStack.pop();
    Dep.target = targetStack[targetStack.length - 1];
  }
  var arrayProto = Array.prototype;
  var arrayMethods = Object.create(arrayProto);
  var methodsToPatch = [
    "push",
    "pop",
    "shift",
    "unshift",
    "splice",
    "sort",
    "reverse"
  ];
  methodsToPatch.forEach(function(method) {
    var original = arrayProto[method];
    def(arrayMethods, method, function mutator() {
      var args = [];
      for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
      }
      var result = original.apply(this, args);
      var ob = this.__ob__;
      var inserted;
      switch (method) {
        case "push":
        case "unshift":
          inserted = args;
          break;
        case "splice":
          inserted = args.slice(2);
          break;
      }
      if (inserted)
        ob.observeArray(inserted);
      {
        ob.dep.notify();
      }
      return result;
    });
  });
  var arrayKeys = Object.getOwnPropertyNames(arrayMethods);
  var NO_INITIAL_VALUE = {};
  var shouldObserve = true;
  function toggleObserving(value) {
    shouldObserve = value;
  }
  var mockDep = {
    notify: noop,
    depend: noop,
    addSub: noop,
    removeSub: noop
  };
  var Observer = (
    /** @class */
    function() {
      function Observer2(value, shallow, mock) {
        if (shallow === void 0) {
          shallow = false;
        }
        if (mock === void 0) {
          mock = false;
        }
        this.value = value;
        this.shallow = shallow;
        this.mock = mock;
        this.dep = mock ? mockDep : new Dep();
        this.vmCount = 0;
        def(value, "__ob__", this);
        if (isArray(value)) {
          if (!mock) {
            if (hasProto) {
              value.__proto__ = arrayMethods;
            } else {
              for (var i = 0, l = arrayKeys.length; i < l; i++) {
                var key = arrayKeys[i];
                def(value, key, arrayMethods[key]);
              }
            }
          }
          if (!shallow) {
            this.observeArray(value);
          }
        } else {
          var keys = Object.keys(value);
          for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            defineReactive(value, key, NO_INITIAL_VALUE, void 0, shallow, mock);
          }
        }
      }
      Observer2.prototype.observeArray = function(value) {
        for (var i = 0, l = value.length; i < l; i++) {
          observe(value[i], false, this.mock);
        }
      };
      return Observer2;
    }()
  );
  function observe(value, shallow, ssrMockReactivity) {
    if (value && hasOwn(value, "__ob__") && value.__ob__ instanceof Observer) {
      return value.__ob__;
    }
    if (shouldObserve && (ssrMockReactivity || !isServerRendering()) && (isArray(value) || isPlainObject(value)) && Object.isExtensible(value) && !value.__v_skip && !isRef(value) && !(value instanceof VNode)) {
      return new Observer(value, shallow, ssrMockReactivity);
    }
  }
  function defineReactive(obj, key, val, customSetter, shallow, mock, observeEvenIfShallow) {
    if (observeEvenIfShallow === void 0) {
      observeEvenIfShallow = false;
    }
    var dep = new Dep();
    var property = Object.getOwnPropertyDescriptor(obj, key);
    if (property && property.configurable === false) {
      return;
    }
    var getter = property && property.get;
    var setter = property && property.set;
    if ((!getter || setter) && (val === NO_INITIAL_VALUE || arguments.length === 2)) {
      val = obj[key];
    }
    var childOb = shallow ? val && val.__ob__ : observe(val, false, mock);
    Object.defineProperty(obj, key, {
      enumerable: true,
      configurable: true,
      get: function reactiveGetter() {
        var value = getter ? getter.call(obj) : val;
        if (Dep.target) {
          {
            dep.depend();
          }
          if (childOb) {
            childOb.dep.depend();
            if (isArray(value)) {
              dependArray(value);
            }
          }
        }
        return isRef(value) && !shallow ? value.value : value;
      },
      set: function reactiveSetter(newVal) {
        var value = getter ? getter.call(obj) : val;
        if (!hasChanged(value, newVal)) {
          return;
        }
        if (setter) {
          setter.call(obj, newVal);
        } else if (getter) {
          return;
        } else if (!shallow && isRef(value) && !isRef(newVal)) {
          value.value = newVal;
          return;
        } else {
          val = newVal;
        }
        childOb = shallow ? newVal && newVal.__ob__ : observe(newVal, false, mock);
        {
          dep.notify();
        }
      }
    });
    return dep;
  }
  function set(target2, key, val) {
    if (isReadonly(target2)) {
      return;
    }
    var ob = target2.__ob__;
    if (isArray(target2) && isValidArrayIndex(key)) {
      target2.length = Math.max(target2.length, key);
      target2.splice(key, 1, val);
      if (ob && !ob.shallow && ob.mock) {
        observe(val, false, true);
      }
      return val;
    }
    if (key in target2 && !(key in Object.prototype)) {
      target2[key] = val;
      return val;
    }
    if (target2._isVue || ob && ob.vmCount) {
      return val;
    }
    if (!ob) {
      target2[key] = val;
      return val;
    }
    defineReactive(ob.value, key, val, void 0, ob.shallow, ob.mock);
    {
      ob.dep.notify();
    }
    return val;
  }
  function del(target2, key) {
    if (isArray(target2) && isValidArrayIndex(key)) {
      target2.splice(key, 1);
      return;
    }
    var ob = target2.__ob__;
    if (target2._isVue || ob && ob.vmCount) {
      return;
    }
    if (isReadonly(target2)) {
      return;
    }
    if (!hasOwn(target2, key)) {
      return;
    }
    delete target2[key];
    if (!ob) {
      return;
    }
    {
      ob.dep.notify();
    }
  }
  function dependArray(value) {
    for (var e = void 0, i = 0, l = value.length; i < l; i++) {
      e = value[i];
      if (e && e.__ob__) {
        e.__ob__.dep.depend();
      }
      if (isArray(e)) {
        dependArray(e);
      }
    }
  }
  function shallowReactive(target2) {
    makeReactive(target2, true);
    def(target2, "__v_isShallow", true);
    return target2;
  }
  function makeReactive(target2, shallow) {
    if (!isReadonly(target2)) {
      observe(
        target2,
        shallow,
        isServerRendering()
        /* ssr mock reactivity */
      );
    }
  }
  function isReadonly(value) {
    return !!(value && value.__v_isReadonly);
  }
  function isRef(r) {
    return !!(r && r.__v_isRef === true);
  }
  function proxyWithRefUnwrap(target2, source, key) {
    Object.defineProperty(target2, key, {
      enumerable: true,
      configurable: true,
      get: function() {
        var val = source[key];
        if (isRef(val)) {
          return val.value;
        } else {
          var ob = val && val.__ob__;
          if (ob)
            ob.dep.depend();
          return val;
        }
      },
      set: function(value) {
        var oldValue = source[key];
        if (isRef(oldValue) && !isRef(value)) {
          oldValue.value = value;
        } else {
          source[key] = value;
        }
      }
    });
  }
  var activeEffectScope;
  var EffectScope = (
    /** @class */
    function() {
      function EffectScope2(detached) {
        if (detached === void 0) {
          detached = false;
        }
        this.detached = detached;
        this.active = true;
        this.effects = [];
        this.cleanups = [];
        this.parent = activeEffectScope;
        if (!detached && activeEffectScope) {
          this.index = (activeEffectScope.scopes || (activeEffectScope.scopes = [])).push(this) - 1;
        }
      }
      EffectScope2.prototype.run = function(fn) {
        if (this.active) {
          var currentEffectScope = activeEffectScope;
          try {
            activeEffectScope = this;
            return fn();
          } finally {
            activeEffectScope = currentEffectScope;
          }
        }
      };
      EffectScope2.prototype.on = function() {
        activeEffectScope = this;
      };
      EffectScope2.prototype.off = function() {
        activeEffectScope = this.parent;
      };
      EffectScope2.prototype.stop = function(fromParent) {
        if (this.active) {
          var i = void 0, l = void 0;
          for (i = 0, l = this.effects.length; i < l; i++) {
            this.effects[i].teardown();
          }
          for (i = 0, l = this.cleanups.length; i < l; i++) {
            this.cleanups[i]();
          }
          if (this.scopes) {
            for (i = 0, l = this.scopes.length; i < l; i++) {
              this.scopes[i].stop(true);
            }
          }
          if (!this.detached && this.parent && !fromParent) {
            var last = this.parent.scopes.pop();
            if (last && last !== this) {
              this.parent.scopes[this.index] = last;
              last.index = this.index;
            }
          }
          this.parent = void 0;
          this.active = false;
        }
      };
      return EffectScope2;
    }()
  );
  function recordEffectScope(effect, scope) {
    if (scope === void 0) {
      scope = activeEffectScope;
    }
    if (scope && scope.active) {
      scope.effects.push(effect);
    }
  }
  function getCurrentScope() {
    return activeEffectScope;
  }
  function resolveProvided(vm) {
    var existing = vm._provided;
    var parentProvides = vm.$parent && vm.$parent._provided;
    if (parentProvides === existing) {
      return vm._provided = Object.create(parentProvides);
    } else {
      return existing;
    }
  }
  var normalizeEvent = cached(function(name) {
    var passive = name.charAt(0) === "&";
    name = passive ? name.slice(1) : name;
    var once2 = name.charAt(0) === "~";
    name = once2 ? name.slice(1) : name;
    var capture = name.charAt(0) === "!";
    name = capture ? name.slice(1) : name;
    return {
      name,
      once: once2,
      capture,
      passive
    };
  });
  function createFnInvoker(fns, vm) {
    function invoker() {
      var fns2 = invoker.fns;
      if (isArray(fns2)) {
        var cloned = fns2.slice();
        for (var i = 0; i < cloned.length; i++) {
          invokeWithErrorHandling(cloned[i], null, arguments, vm, "v-on handler");
        }
      } else {
        return invokeWithErrorHandling(fns2, null, arguments, vm, "v-on handler");
      }
    }
    invoker.fns = fns;
    return invoker;
  }
  function updateListeners(on, oldOn, add2, remove2, createOnceHandler2, vm) {
    var name, cur, old, event;
    for (name in on) {
      cur = on[name];
      old = oldOn[name];
      event = normalizeEvent(name);
      if (isUndef(cur)) ;
      else if (isUndef(old)) {
        if (isUndef(cur.fns)) {
          cur = on[name] = createFnInvoker(cur, vm);
        }
        if (isTrue(event.once)) {
          cur = on[name] = createOnceHandler2(event.name, cur, event.capture);
        }
        add2(event.name, cur, event.capture, event.passive, event.params);
      } else if (cur !== old) {
        old.fns = cur;
        on[name] = old;
      }
    }
    for (name in oldOn) {
      if (isUndef(on[name])) {
        event = normalizeEvent(name);
        remove2(event.name, oldOn[name], event.capture);
      }
    }
  }
  function mergeVNodeHook(def2, hookKey, hook) {
    if (def2 instanceof VNode) {
      def2 = def2.data.hook || (def2.data.hook = {});
    }
    var invoker;
    var oldHook = def2[hookKey];
    function wrappedHook() {
      hook.apply(this, arguments);
      remove$2(invoker.fns, wrappedHook);
    }
    if (isUndef(oldHook)) {
      invoker = createFnInvoker([wrappedHook]);
    } else {
      if (isDef(oldHook.fns) && isTrue(oldHook.merged)) {
        invoker = oldHook;
        invoker.fns.push(wrappedHook);
      } else {
        invoker = createFnInvoker([oldHook, wrappedHook]);
      }
    }
    invoker.merged = true;
    def2[hookKey] = invoker;
  }
  function extractPropsFromVNodeData(data, Ctor, tag) {
    var propOptions = Ctor.options.props;
    if (isUndef(propOptions)) {
      return;
    }
    var res = {};
    var attrs2 = data.attrs, props2 = data.props;
    if (isDef(attrs2) || isDef(props2)) {
      for (var key in propOptions) {
        var altKey = hyphenate(key);
        checkProp(res, props2, key, altKey, true) || checkProp(res, attrs2, key, altKey, false);
      }
    }
    return res;
  }
  function checkProp(res, hash, key, altKey, preserve) {
    if (isDef(hash)) {
      if (hasOwn(hash, key)) {
        res[key] = hash[key];
        if (!preserve) {
          delete hash[key];
        }
        return true;
      } else if (hasOwn(hash, altKey)) {
        res[key] = hash[altKey];
        if (!preserve) {
          delete hash[altKey];
        }
        return true;
      }
    }
    return false;
  }
  function simpleNormalizeChildren(children) {
    for (var i = 0; i < children.length; i++) {
      if (isArray(children[i])) {
        return Array.prototype.concat.apply([], children);
      }
    }
    return children;
  }
  function normalizeChildren(children) {
    return isPrimitive(children) ? [createTextVNode(children)] : isArray(children) ? normalizeArrayChildren(children) : void 0;
  }
  function isTextNode(node) {
    return isDef(node) && isDef(node.text) && isFalse(node.isComment);
  }
  function normalizeArrayChildren(children, nestedIndex) {
    var res = [];
    var i, c, lastIndex, last;
    for (i = 0; i < children.length; i++) {
      c = children[i];
      if (isUndef(c) || typeof c === "boolean")
        continue;
      lastIndex = res.length - 1;
      last = res[lastIndex];
      if (isArray(c)) {
        if (c.length > 0) {
          c = normalizeArrayChildren(c, "".concat(nestedIndex || "", "_").concat(i));
          if (isTextNode(c[0]) && isTextNode(last)) {
            res[lastIndex] = createTextVNode(last.text + c[0].text);
            c.shift();
          }
          res.push.apply(res, c);
        }
      } else if (isPrimitive(c)) {
        if (isTextNode(last)) {
          res[lastIndex] = createTextVNode(last.text + c);
        } else if (c !== "") {
          res.push(createTextVNode(c));
        }
      } else {
        if (isTextNode(c) && isTextNode(last)) {
          res[lastIndex] = createTextVNode(last.text + c.text);
        } else {
          if (isTrue(children._isVList) && isDef(c.tag) && isUndef(c.key) && isDef(nestedIndex)) {
            c.key = "__vlist".concat(nestedIndex, "_").concat(i, "__");
          }
          res.push(c);
        }
      }
    }
    return res;
  }
  function renderList(val, render) {
    var ret = null, i, l, keys, key;
    if (isArray(val) || typeof val === "string") {
      ret = new Array(val.length);
      for (i = 0, l = val.length; i < l; i++) {
        ret[i] = render(val[i], i);
      }
    } else if (typeof val === "number") {
      ret = new Array(val);
      for (i = 0; i < val; i++) {
        ret[i] = render(i + 1, i);
      }
    } else if (isObject(val)) {
      if (hasSymbol && val[Symbol.iterator]) {
        ret = [];
        var iterator = val[Symbol.iterator]();
        var result = iterator.next();
        while (!result.done) {
          ret.push(render(result.value, ret.length));
          result = iterator.next();
        }
      } else {
        keys = Object.keys(val);
        ret = new Array(keys.length);
        for (i = 0, l = keys.length; i < l; i++) {
          key = keys[i];
          ret[i] = render(val[key], key, i);
        }
      }
    }
    if (!isDef(ret)) {
      ret = [];
    }
    ret._isVList = true;
    return ret;
  }
  function renderSlot(name, fallbackRender, props2, bindObject) {
    var scopedSlotFn = this.$scopedSlots[name];
    var nodes;
    if (scopedSlotFn) {
      props2 = props2 || {};
      if (bindObject) {
        props2 = extend(extend({}, bindObject), props2);
      }
      nodes = scopedSlotFn(props2) || (isFunction(fallbackRender) ? fallbackRender() : fallbackRender);
    } else {
      nodes = this.$slots[name] || (isFunction(fallbackRender) ? fallbackRender() : fallbackRender);
    }
    var target2 = props2 && props2.slot;
    if (target2) {
      return this.$createElement("template", { slot: target2 }, nodes);
    } else {
      return nodes;
    }
  }
  function resolveFilter(id) {
    return resolveAsset(this.$options, "filters", id) || identity;
  }
  function isKeyNotMatch(expect, actual) {
    if (isArray(expect)) {
      return expect.indexOf(actual) === -1;
    } else {
      return expect !== actual;
    }
  }
  function checkKeyCodes(eventKeyCode, key, builtInKeyCode, eventKeyName, builtInKeyName) {
    var mappedKeyCode = config.keyCodes[key] || builtInKeyCode;
    if (builtInKeyName && eventKeyName && !config.keyCodes[key]) {
      return isKeyNotMatch(builtInKeyName, eventKeyName);
    } else if (mappedKeyCode) {
      return isKeyNotMatch(mappedKeyCode, eventKeyCode);
    } else if (eventKeyName) {
      return hyphenate(eventKeyName) !== key;
    }
    return eventKeyCode === void 0;
  }
  function bindObjectProps(data, tag, value, asProp, isSync) {
    if (value) {
      if (!isObject(value)) ;
      else {
        if (isArray(value)) {
          value = toObject(value);
        }
        var hash = void 0;
        var _loop_1 = function(key2) {
          if (key2 === "class" || key2 === "style" || isReservedAttribute(key2)) {
            hash = data;
          } else {
            var type = data.attrs && data.attrs.type;
            hash = asProp || config.mustUseProp(tag, type, key2) ? data.domProps || (data.domProps = {}) : data.attrs || (data.attrs = {});
          }
          var camelizedKey = camelize(key2);
          var hyphenatedKey = hyphenate(key2);
          if (!(camelizedKey in hash) && !(hyphenatedKey in hash)) {
            hash[key2] = value[key2];
            if (isSync) {
              var on = data.on || (data.on = {});
              on["update:".concat(key2)] = function($event) {
                value[key2] = $event;
              };
            }
          }
        };
        for (var key in value) {
          _loop_1(key);
        }
      }
    }
    return data;
  }
  function renderStatic(index2, isInFor) {
    var cached2 = this._staticTrees || (this._staticTrees = []);
    var tree = cached2[index2];
    if (tree && !isInFor) {
      return tree;
    }
    tree = cached2[index2] = this.$options.staticRenderFns[index2].call(
      this._renderProxy,
      this._c,
      this
      // for render fns generated for functional component templates
    );
    markStatic(tree, "__static__".concat(index2), false);
    return tree;
  }
  function markOnce(tree, index2, key) {
    markStatic(tree, "__once__".concat(index2).concat(key ? "_".concat(key) : ""), true);
    return tree;
  }
  function markStatic(tree, key, isOnce) {
    if (isArray(tree)) {
      for (var i = 0; i < tree.length; i++) {
        if (tree[i] && typeof tree[i] !== "string") {
          markStaticNode(tree[i], "".concat(key, "_").concat(i), isOnce);
        }
      }
    } else {
      markStaticNode(tree, key, isOnce);
    }
  }
  function markStaticNode(node, key, isOnce) {
    node.isStatic = true;
    node.key = key;
    node.isOnce = isOnce;
  }
  function bindObjectListeners(data, value) {
    if (value) {
      if (!isPlainObject(value)) ;
      else {
        var on = data.on = data.on ? extend({}, data.on) : {};
        for (var key in value) {
          var existing = on[key];
          var ours = value[key];
          on[key] = existing ? [].concat(existing, ours) : ours;
        }
      }
    }
    return data;
  }
  function resolveScopedSlots(fns, res, hasDynamicKeys, contentHashKey) {
    res = res || { $stable: !hasDynamicKeys };
    for (var i = 0; i < fns.length; i++) {
      var slot = fns[i];
      if (isArray(slot)) {
        resolveScopedSlots(slot, res, hasDynamicKeys);
      } else if (slot) {
        if (slot.proxy) {
          slot.fn.proxy = true;
        }
        res[slot.key] = slot.fn;
      }
    }
    if (contentHashKey) {
      res.$key = contentHashKey;
    }
    return res;
  }
  function bindDynamicKeys(baseObj, values) {
    for (var i = 0; i < values.length; i += 2) {
      var key = values[i];
      if (typeof key === "string" && key) {
        baseObj[values[i]] = values[i + 1];
      }
    }
    return baseObj;
  }
  function prependModifier(value, symbol) {
    return typeof value === "string" ? symbol + value : value;
  }
  function installRenderHelpers(target2) {
    target2._o = markOnce;
    target2._n = toNumber;
    target2._s = toString;
    target2._l = renderList;
    target2._t = renderSlot;
    target2._q = looseEqual;
    target2._i = looseIndexOf;
    target2._m = renderStatic;
    target2._f = resolveFilter;
    target2._k = checkKeyCodes;
    target2._b = bindObjectProps;
    target2._v = createTextVNode;
    target2._e = createEmptyVNode;
    target2._u = resolveScopedSlots;
    target2._g = bindObjectListeners;
    target2._d = bindDynamicKeys;
    target2._p = prependModifier;
  }
  function resolveSlots(children, context) {
    if (!children || !children.length) {
      return {};
    }
    var slots = {};
    for (var i = 0, l = children.length; i < l; i++) {
      var child = children[i];
      var data = child.data;
      if (data && data.attrs && data.attrs.slot) {
        delete data.attrs.slot;
      }
      if ((child.context === context || child.fnContext === context) && data && data.slot != null) {
        var name_1 = data.slot;
        var slot = slots[name_1] || (slots[name_1] = []);
        if (child.tag === "template") {
          slot.push.apply(slot, child.children || []);
        } else {
          slot.push(child);
        }
      } else {
        (slots.default || (slots.default = [])).push(child);
      }
    }
    for (var name_2 in slots) {
      if (slots[name_2].every(isWhitespace)) {
        delete slots[name_2];
      }
    }
    return slots;
  }
  function isWhitespace(node) {
    return node.isComment && !node.asyncFactory || node.text === " ";
  }
  function isAsyncPlaceholder(node) {
    return node.isComment && node.asyncFactory;
  }
  function normalizeScopedSlots(ownerVm, scopedSlots, normalSlots, prevScopedSlots) {
    var res;
    var hasNormalSlots = Object.keys(normalSlots).length > 0;
    var isStable = scopedSlots ? !!scopedSlots.$stable : !hasNormalSlots;
    var key = scopedSlots && scopedSlots.$key;
    if (!scopedSlots) {
      res = {};
    } else if (scopedSlots._normalized) {
      return scopedSlots._normalized;
    } else if (isStable && prevScopedSlots && prevScopedSlots !== emptyObject && key === prevScopedSlots.$key && !hasNormalSlots && !prevScopedSlots.$hasNormal) {
      return prevScopedSlots;
    } else {
      res = {};
      for (var key_1 in scopedSlots) {
        if (scopedSlots[key_1] && key_1[0] !== "$") {
          res[key_1] = normalizeScopedSlot(ownerVm, normalSlots, key_1, scopedSlots[key_1]);
        }
      }
    }
    for (var key_2 in normalSlots) {
      if (!(key_2 in res)) {
        res[key_2] = proxyNormalSlot(normalSlots, key_2);
      }
    }
    if (scopedSlots && Object.isExtensible(scopedSlots)) {
      scopedSlots._normalized = res;
    }
    def(res, "$stable", isStable);
    def(res, "$key", key);
    def(res, "$hasNormal", hasNormalSlots);
    return res;
  }
  function normalizeScopedSlot(vm, normalSlots, key, fn) {
    var normalized = function() {
      var cur = currentInstance;
      setCurrentInstance(vm);
      var res = arguments.length ? fn.apply(null, arguments) : fn({});
      res = res && typeof res === "object" && !isArray(res) ? [res] : normalizeChildren(res);
      var vnode = res && res[0];
      setCurrentInstance(cur);
      return res && (!vnode || res.length === 1 && vnode.isComment && !isAsyncPlaceholder(vnode)) ? void 0 : res;
    };
    if (fn.proxy) {
      Object.defineProperty(normalSlots, key, {
        get: normalized,
        enumerable: true,
        configurable: true
      });
    }
    return normalized;
  }
  function proxyNormalSlot(slots, key) {
    return function() {
      return slots[key];
    };
  }
  function initSetup(vm) {
    var options = vm.$options;
    var setup = options.setup;
    if (setup) {
      var ctx = vm._setupContext = createSetupContext(vm);
      setCurrentInstance(vm);
      pushTarget();
      var setupResult = invokeWithErrorHandling(setup, null, [vm._props || shallowReactive({}), ctx], vm, "setup");
      popTarget();
      setCurrentInstance();
      if (isFunction(setupResult)) {
        options.render = setupResult;
      } else if (isObject(setupResult)) {
        vm._setupState = setupResult;
        if (!setupResult.__sfc) {
          for (var key in setupResult) {
            if (!isReserved(key)) {
              proxyWithRefUnwrap(vm, setupResult, key);
            }
          }
        } else {
          var proxy2 = vm._setupProxy = {};
          for (var key in setupResult) {
            if (key !== "__sfc") {
              proxyWithRefUnwrap(proxy2, setupResult, key);
            }
          }
        }
      } else ;
    }
  }
  function createSetupContext(vm) {
    return {
      get attrs() {
        if (!vm._attrsProxy) {
          var proxy2 = vm._attrsProxy = {};
          def(proxy2, "_v_attr_proxy", true);
          syncSetupProxy(proxy2, vm.$attrs, emptyObject, vm, "$attrs");
        }
        return vm._attrsProxy;
      },
      get listeners() {
        if (!vm._listenersProxy) {
          var proxy2 = vm._listenersProxy = {};
          syncSetupProxy(proxy2, vm.$listeners, emptyObject, vm, "$listeners");
        }
        return vm._listenersProxy;
      },
      get slots() {
        return initSlotsProxy(vm);
      },
      emit: bind(vm.$emit, vm),
      expose: function(exposed) {
        if (exposed) {
          Object.keys(exposed).forEach(function(key) {
            return proxyWithRefUnwrap(vm, exposed, key);
          });
        }
      }
    };
  }
  function syncSetupProxy(to, from, prev, instance, type) {
    var changed = false;
    for (var key in from) {
      if (!(key in to)) {
        changed = true;
        defineProxyAttr(to, key, instance, type);
      } else if (from[key] !== prev[key]) {
        changed = true;
      }
    }
    for (var key in to) {
      if (!(key in from)) {
        changed = true;
        delete to[key];
      }
    }
    return changed;
  }
  function defineProxyAttr(proxy2, key, instance, type) {
    Object.defineProperty(proxy2, key, {
      enumerable: true,
      configurable: true,
      get: function() {
        return instance[type][key];
      }
    });
  }
  function initSlotsProxy(vm) {
    if (!vm._slotsProxy) {
      syncSetupSlots(vm._slotsProxy = {}, vm.$scopedSlots);
    }
    return vm._slotsProxy;
  }
  function syncSetupSlots(to, from) {
    for (var key in from) {
      to[key] = from[key];
    }
    for (var key in to) {
      if (!(key in from)) {
        delete to[key];
      }
    }
  }
  function initRender(vm) {
    vm._vnode = null;
    vm._staticTrees = null;
    var options = vm.$options;
    var parentVnode = vm.$vnode = options._parentVnode;
    var renderContext = parentVnode && parentVnode.context;
    vm.$slots = resolveSlots(options._renderChildren, renderContext);
    vm.$scopedSlots = parentVnode ? normalizeScopedSlots(vm.$parent, parentVnode.data.scopedSlots, vm.$slots) : emptyObject;
    vm._c = function(a, b, c, d) {
      return createElement$1(vm, a, b, c, d, false);
    };
    vm.$createElement = function(a, b, c, d) {
      return createElement$1(vm, a, b, c, d, true);
    };
    var parentData = parentVnode && parentVnode.data;
    {
      defineReactive(vm, "$attrs", parentData && parentData.attrs || emptyObject, null, true);
      defineReactive(vm, "$listeners", options._parentListeners || emptyObject, null, true);
    }
  }
  var currentRenderingInstance = null;
  function renderMixin(Vue2) {
    installRenderHelpers(Vue2.prototype);
    Vue2.prototype.$nextTick = function(fn) {
      return nextTick(fn, this);
    };
    Vue2.prototype._render = function() {
      var vm = this;
      var _a = vm.$options, render = _a.render, _parentVnode = _a._parentVnode;
      if (_parentVnode && vm._isMounted) {
        vm.$scopedSlots = normalizeScopedSlots(vm.$parent, _parentVnode.data.scopedSlots, vm.$slots, vm.$scopedSlots);
        if (vm._slotsProxy) {
          syncSetupSlots(vm._slotsProxy, vm.$scopedSlots);
        }
      }
      vm.$vnode = _parentVnode;
      var prevInst = currentInstance;
      var prevRenderInst = currentRenderingInstance;
      var vnode;
      try {
        setCurrentInstance(vm);
        currentRenderingInstance = vm;
        vnode = render.call(vm._renderProxy, vm.$createElement);
      } catch (e) {
        handleError(e, vm, "render");
        {
          vnode = vm._vnode;
        }
      } finally {
        currentRenderingInstance = prevRenderInst;
        setCurrentInstance(prevInst);
      }
      if (isArray(vnode) && vnode.length === 1) {
        vnode = vnode[0];
      }
      if (!(vnode instanceof VNode)) {
        vnode = createEmptyVNode();
      }
      vnode.parent = _parentVnode;
      return vnode;
    };
  }
  function ensureCtor(comp, base) {
    if (comp.__esModule || hasSymbol && comp[Symbol.toStringTag] === "Module") {
      comp = comp.default;
    }
    return isObject(comp) ? base.extend(comp) : comp;
  }
  function createAsyncPlaceholder(factory, data, context, children, tag) {
    var node = createEmptyVNode();
    node.asyncFactory = factory;
    node.asyncMeta = { data, context, children, tag };
    return node;
  }
  function resolveAsyncComponent(factory, baseCtor) {
    if (isTrue(factory.error) && isDef(factory.errorComp)) {
      return factory.errorComp;
    }
    if (isDef(factory.resolved)) {
      return factory.resolved;
    }
    var owner = currentRenderingInstance;
    if (owner && isDef(factory.owners) && factory.owners.indexOf(owner) === -1) {
      factory.owners.push(owner);
    }
    if (isTrue(factory.loading) && isDef(factory.loadingComp)) {
      return factory.loadingComp;
    }
    if (owner && !isDef(factory.owners)) {
      var owners_1 = factory.owners = [owner];
      var sync_1 = true;
      var timerLoading_1 = null;
      var timerTimeout_1 = null;
      owner.$on("hook:destroyed", function() {
        return remove$2(owners_1, owner);
      });
      var forceRender_1 = function(renderCompleted) {
        for (var i = 0, l = owners_1.length; i < l; i++) {
          owners_1[i].$forceUpdate();
        }
        if (renderCompleted) {
          owners_1.length = 0;
          if (timerLoading_1 !== null) {
            clearTimeout(timerLoading_1);
            timerLoading_1 = null;
          }
          if (timerTimeout_1 !== null) {
            clearTimeout(timerTimeout_1);
            timerTimeout_1 = null;
          }
        }
      };
      var resolve = once(function(res) {
        factory.resolved = ensureCtor(res, baseCtor);
        if (!sync_1) {
          forceRender_1(true);
        } else {
          owners_1.length = 0;
        }
      });
      var reject_1 = once(function(reason) {
        if (isDef(factory.errorComp)) {
          factory.error = true;
          forceRender_1(true);
        }
      });
      var res_1 = factory(resolve, reject_1);
      if (isObject(res_1)) {
        if (isPromise(res_1)) {
          if (isUndef(factory.resolved)) {
            res_1.then(resolve, reject_1);
          }
        } else if (isPromise(res_1.component)) {
          res_1.component.then(resolve, reject_1);
          if (isDef(res_1.error)) {
            factory.errorComp = ensureCtor(res_1.error, baseCtor);
          }
          if (isDef(res_1.loading)) {
            factory.loadingComp = ensureCtor(res_1.loading, baseCtor);
            if (res_1.delay === 0) {
              factory.loading = true;
            } else {
              timerLoading_1 = setTimeout(function() {
                timerLoading_1 = null;
                if (isUndef(factory.resolved) && isUndef(factory.error)) {
                  factory.loading = true;
                  forceRender_1(false);
                }
              }, res_1.delay || 200);
            }
          }
          if (isDef(res_1.timeout)) {
            timerTimeout_1 = setTimeout(function() {
              timerTimeout_1 = null;
              if (isUndef(factory.resolved)) {
                reject_1(null);
              }
            }, res_1.timeout);
          }
        }
      }
      sync_1 = false;
      return factory.loading ? factory.loadingComp : factory.resolved;
    }
  }
  function getFirstComponentChild(children) {
    if (isArray(children)) {
      for (var i = 0; i < children.length; i++) {
        var c = children[i];
        if (isDef(c) && (isDef(c.componentOptions) || isAsyncPlaceholder(c))) {
          return c;
        }
      }
    }
  }
  var SIMPLE_NORMALIZE = 1;
  var ALWAYS_NORMALIZE = 2;
  function createElement$1(context, tag, data, children, normalizationType, alwaysNormalize) {
    if (isArray(data) || isPrimitive(data)) {
      normalizationType = children;
      children = data;
      data = void 0;
    }
    if (isTrue(alwaysNormalize)) {
      normalizationType = ALWAYS_NORMALIZE;
    }
    return _createElement(context, tag, data, children, normalizationType);
  }
  function _createElement(context, tag, data, children, normalizationType) {
    if (isDef(data) && isDef(data.__ob__)) {
      return createEmptyVNode();
    }
    if (isDef(data) && isDef(data.is)) {
      tag = data.is;
    }
    if (!tag) {
      return createEmptyVNode();
    }
    if (isArray(children) && isFunction(children[0])) {
      data = data || {};
      data.scopedSlots = { default: children[0] };
      children.length = 0;
    }
    if (normalizationType === ALWAYS_NORMALIZE) {
      children = normalizeChildren(children);
    } else if (normalizationType === SIMPLE_NORMALIZE) {
      children = simpleNormalizeChildren(children);
    }
    var vnode, ns;
    if (typeof tag === "string") {
      var Ctor = void 0;
      ns = context.$vnode && context.$vnode.ns || config.getTagNamespace(tag);
      if (config.isReservedTag(tag)) {
        vnode = new VNode(config.parsePlatformTagName(tag), data, children, void 0, void 0, context);
      } else if ((!data || !data.pre) && isDef(Ctor = resolveAsset(context.$options, "components", tag))) {
        vnode = createComponent(Ctor, data, context, children, tag);
      } else {
        vnode = new VNode(tag, data, children, void 0, void 0, context);
      }
    } else {
      vnode = createComponent(tag, data, context, children);
    }
    if (isArray(vnode)) {
      return vnode;
    } else if (isDef(vnode)) {
      if (isDef(ns))
        applyNS(vnode, ns);
      if (isDef(data))
        registerDeepBindings(data);
      return vnode;
    } else {
      return createEmptyVNode();
    }
  }
  function applyNS(vnode, ns, force) {
    vnode.ns = ns;
    if (vnode.tag === "foreignObject") {
      ns = void 0;
      force = true;
    }
    if (isDef(vnode.children)) {
      for (var i = 0, l = vnode.children.length; i < l; i++) {
        var child = vnode.children[i];
        if (isDef(child.tag) && (isUndef(child.ns) || isTrue(force) && child.tag !== "svg")) {
          applyNS(child, ns, force);
        }
      }
    }
  }
  function registerDeepBindings(data) {
    if (isObject(data.style)) {
      traverse(data.style);
    }
    if (isObject(data.class)) {
      traverse(data.class);
    }
  }
  function handleError(err, vm, info) {
    pushTarget();
    try {
      if (vm) {
        var cur = vm;
        while (cur = cur.$parent) {
          var hooks2 = cur.$options.errorCaptured;
          if (hooks2) {
            for (var i = 0; i < hooks2.length; i++) {
              try {
                var capture = hooks2[i].call(cur, err, vm, info) === false;
                if (capture)
                  return;
              } catch (e) {
                globalHandleError(e, cur, "errorCaptured hook");
              }
            }
          }
        }
      }
      globalHandleError(err, vm, info);
    } finally {
      popTarget();
    }
  }
  function invokeWithErrorHandling(handler, context, args, vm, info) {
    var res;
    try {
      res = args ? handler.apply(context, args) : handler.call(context);
      if (res && !res._isVue && isPromise(res) && !res._handled) {
        res.catch(function(e) {
          return handleError(e, vm, info + " (Promise/async)");
        });
        res._handled = true;
      }
    } catch (e) {
      handleError(e, vm, info);
    }
    return res;
  }
  function globalHandleError(err, vm, info) {
    if (config.errorHandler) {
      try {
        return config.errorHandler.call(null, err, vm, info);
      } catch (e) {
        if (e !== err) {
          logError(e);
        }
      }
    }
    logError(err);
  }
  function logError(err, vm, info) {
    if (inBrowser && typeof console !== "undefined") {
      console.error(err);
    } else {
      throw err;
    }
  }
  var isUsingMicroTask = false;
  var callbacks = [];
  var pending = false;
  function flushCallbacks() {
    pending = false;
    var copies = callbacks.slice(0);
    callbacks.length = 0;
    for (var i = 0; i < copies.length; i++) {
      copies[i]();
    }
  }
  var timerFunc;
  if (typeof Promise !== "undefined" && isNative(Promise)) {
    var p_1 = Promise.resolve();
    timerFunc = function() {
      p_1.then(flushCallbacks);
      if (isIOS)
        setTimeout(noop);
    };
    isUsingMicroTask = true;
  } else if (!isIE && typeof MutationObserver !== "undefined" && (isNative(MutationObserver) || // PhantomJS and iOS 7.x
  MutationObserver.toString() === "[object MutationObserverConstructor]")) {
    var counter_1 = 1;
    var observer = new MutationObserver(flushCallbacks);
    var textNode_1 = document.createTextNode(String(counter_1));
    observer.observe(textNode_1, {
      characterData: true
    });
    timerFunc = function() {
      counter_1 = (counter_1 + 1) % 2;
      textNode_1.data = String(counter_1);
    };
    isUsingMicroTask = true;
  } else if (typeof setImmediate !== "undefined" && isNative(setImmediate)) {
    timerFunc = function() {
      setImmediate(flushCallbacks);
    };
  } else {
    timerFunc = function() {
      setTimeout(flushCallbacks, 0);
    };
  }
  function nextTick(cb, ctx) {
    var _resolve;
    callbacks.push(function() {
      if (cb) {
        try {
          cb.call(ctx);
        } catch (e) {
          handleError(e, ctx, "nextTick");
        }
      } else if (_resolve) {
        _resolve(ctx);
      }
    });
    if (!pending) {
      pending = true;
      timerFunc();
    }
    if (!cb && typeof Promise !== "undefined") {
      return new Promise(function(resolve) {
        _resolve = resolve;
      });
    }
  }
  var version = "2.7.16";
  var seenObjects = new _Set();
  function traverse(val) {
    _traverse(val, seenObjects);
    seenObjects.clear();
    return val;
  }
  function _traverse(val, seen) {
    var i, keys;
    var isA = isArray(val);
    if (!isA && !isObject(val) || val.__v_skip || Object.isFrozen(val) || val instanceof VNode) {
      return;
    }
    if (val.__ob__) {
      var depId = val.__ob__.dep.id;
      if (seen.has(depId)) {
        return;
      }
      seen.add(depId);
    }
    if (isA) {
      i = val.length;
      while (i--)
        _traverse(val[i], seen);
    } else if (isRef(val)) {
      _traverse(val.value, seen);
    } else {
      keys = Object.keys(val);
      i = keys.length;
      while (i--)
        _traverse(val[keys[i]], seen);
    }
  }
  var uid$1 = 0;
  var Watcher = (
    /** @class */
    function() {
      function Watcher2(vm, expOrFn, cb, options, isRenderWatcher) {
        recordEffectScope(
          this,
          // if the active effect scope is manually created (not a component scope),
          // prioritize it
          activeEffectScope && !activeEffectScope._vm ? activeEffectScope : vm ? vm._scope : void 0
        );
        if ((this.vm = vm) && isRenderWatcher) {
          vm._watcher = this;
        }
        if (options) {
          this.deep = !!options.deep;
          this.user = !!options.user;
          this.lazy = !!options.lazy;
          this.sync = !!options.sync;
          this.before = options.before;
        } else {
          this.deep = this.user = this.lazy = this.sync = false;
        }
        this.cb = cb;
        this.id = ++uid$1;
        this.active = true;
        this.post = false;
        this.dirty = this.lazy;
        this.deps = [];
        this.newDeps = [];
        this.depIds = new _Set();
        this.newDepIds = new _Set();
        this.expression = "";
        if (isFunction(expOrFn)) {
          this.getter = expOrFn;
        } else {
          this.getter = parsePath(expOrFn);
          if (!this.getter) {
            this.getter = noop;
          }
        }
        this.value = this.lazy ? void 0 : this.get();
      }
      Watcher2.prototype.get = function() {
        pushTarget(this);
        var value;
        var vm = this.vm;
        try {
          value = this.getter.call(vm, vm);
        } catch (e) {
          if (this.user) {
            handleError(e, vm, 'getter for watcher "'.concat(this.expression, '"'));
          } else {
            throw e;
          }
        } finally {
          if (this.deep) {
            traverse(value);
          }
          popTarget();
          this.cleanupDeps();
        }
        return value;
      };
      Watcher2.prototype.addDep = function(dep) {
        var id = dep.id;
        if (!this.newDepIds.has(id)) {
          this.newDepIds.add(id);
          this.newDeps.push(dep);
          if (!this.depIds.has(id)) {
            dep.addSub(this);
          }
        }
      };
      Watcher2.prototype.cleanupDeps = function() {
        var i = this.deps.length;
        while (i--) {
          var dep = this.deps[i];
          if (!this.newDepIds.has(dep.id)) {
            dep.removeSub(this);
          }
        }
        var tmp = this.depIds;
        this.depIds = this.newDepIds;
        this.newDepIds = tmp;
        this.newDepIds.clear();
        tmp = this.deps;
        this.deps = this.newDeps;
        this.newDeps = tmp;
        this.newDeps.length = 0;
      };
      Watcher2.prototype.update = function() {
        if (this.lazy) {
          this.dirty = true;
        } else if (this.sync) {
          this.run();
        } else {
          queueWatcher(this);
        }
      };
      Watcher2.prototype.run = function() {
        if (this.active) {
          var value = this.get();
          if (value !== this.value || // Deep watchers and watchers on Object/Arrays should fire even
          // when the value is the same, because the value may
          // have mutated.
          isObject(value) || this.deep) {
            var oldValue = this.value;
            this.value = value;
            if (this.user) {
              var info = 'callback for watcher "'.concat(this.expression, '"');
              invokeWithErrorHandling(this.cb, this.vm, [value, oldValue], this.vm, info);
            } else {
              this.cb.call(this.vm, value, oldValue);
            }
          }
        }
      };
      Watcher2.prototype.evaluate = function() {
        this.value = this.get();
        this.dirty = false;
      };
      Watcher2.prototype.depend = function() {
        var i = this.deps.length;
        while (i--) {
          this.deps[i].depend();
        }
      };
      Watcher2.prototype.teardown = function() {
        if (this.vm && !this.vm._isBeingDestroyed) {
          remove$2(this.vm._scope.effects, this);
        }
        if (this.active) {
          var i = this.deps.length;
          while (i--) {
            this.deps[i].removeSub(this);
          }
          this.active = false;
          if (this.onStop) {
            this.onStop();
          }
        }
      };
      return Watcher2;
    }()
  );
  function initEvents(vm) {
    vm._events = /* @__PURE__ */ Object.create(null);
    vm._hasHookEvent = false;
    var listeners = vm.$options._parentListeners;
    if (listeners) {
      updateComponentListeners(vm, listeners);
    }
  }
  var target$1;
  function add$1(event, fn) {
    target$1.$on(event, fn);
  }
  function remove$1(event, fn) {
    target$1.$off(event, fn);
  }
  function createOnceHandler$1(event, fn) {
    var _target = target$1;
    return function onceHandler() {
      var res = fn.apply(null, arguments);
      if (res !== null) {
        _target.$off(event, onceHandler);
      }
    };
  }
  function updateComponentListeners(vm, listeners, oldListeners) {
    target$1 = vm;
    updateListeners(listeners, oldListeners || {}, add$1, remove$1, createOnceHandler$1, vm);
    target$1 = void 0;
  }
  function eventsMixin(Vue2) {
    var hookRE = /^hook:/;
    Vue2.prototype.$on = function(event, fn) {
      var vm = this;
      if (isArray(event)) {
        for (var i = 0, l = event.length; i < l; i++) {
          vm.$on(event[i], fn);
        }
      } else {
        (vm._events[event] || (vm._events[event] = [])).push(fn);
        if (hookRE.test(event)) {
          vm._hasHookEvent = true;
        }
      }
      return vm;
    };
    Vue2.prototype.$once = function(event, fn) {
      var vm = this;
      function on() {
        vm.$off(event, on);
        fn.apply(vm, arguments);
      }
      on.fn = fn;
      vm.$on(event, on);
      return vm;
    };
    Vue2.prototype.$off = function(event, fn) {
      var vm = this;
      if (!arguments.length) {
        vm._events = /* @__PURE__ */ Object.create(null);
        return vm;
      }
      if (isArray(event)) {
        for (var i_1 = 0, l = event.length; i_1 < l; i_1++) {
          vm.$off(event[i_1], fn);
        }
        return vm;
      }
      var cbs = vm._events[event];
      if (!cbs) {
        return vm;
      }
      if (!fn) {
        vm._events[event] = null;
        return vm;
      }
      var cb;
      var i = cbs.length;
      while (i--) {
        cb = cbs[i];
        if (cb === fn || cb.fn === fn) {
          cbs.splice(i, 1);
          break;
        }
      }
      return vm;
    };
    Vue2.prototype.$emit = function(event) {
      var vm = this;
      var cbs = vm._events[event];
      if (cbs) {
        cbs = cbs.length > 1 ? toArray(cbs) : cbs;
        var args = toArray(arguments, 1);
        var info = 'event handler for "'.concat(event, '"');
        for (var i = 0, l = cbs.length; i < l; i++) {
          invokeWithErrorHandling(cbs[i], vm, args, vm, info);
        }
      }
      return vm;
    };
  }
  var activeInstance = null;
  function setActiveInstance(vm) {
    var prevActiveInstance = activeInstance;
    activeInstance = vm;
    return function() {
      activeInstance = prevActiveInstance;
    };
  }
  function initLifecycle(vm) {
    var options = vm.$options;
    var parent = options.parent;
    if (parent && !options.abstract) {
      while (parent.$options.abstract && parent.$parent) {
        parent = parent.$parent;
      }
      parent.$children.push(vm);
    }
    vm.$parent = parent;
    vm.$root = parent ? parent.$root : vm;
    vm.$children = [];
    vm.$refs = {};
    vm._provided = parent ? parent._provided : /* @__PURE__ */ Object.create(null);
    vm._watcher = null;
    vm._inactive = null;
    vm._directInactive = false;
    vm._isMounted = false;
    vm._isDestroyed = false;
    vm._isBeingDestroyed = false;
  }
  function lifecycleMixin(Vue2) {
    Vue2.prototype._update = function(vnode, hydrating) {
      var vm = this;
      var prevEl = vm.$el;
      var prevVnode = vm._vnode;
      var restoreActiveInstance = setActiveInstance(vm);
      vm._vnode = vnode;
      if (!prevVnode) {
        vm.$el = vm.__patch__(
          vm.$el,
          vnode,
          hydrating,
          false
          /* removeOnly */
        );
      } else {
        vm.$el = vm.__patch__(prevVnode, vnode);
      }
      restoreActiveInstance();
      if (prevEl) {
        prevEl.__vue__ = null;
      }
      if (vm.$el) {
        vm.$el.__vue__ = vm;
      }
      var wrapper = vm;
      while (wrapper && wrapper.$vnode && wrapper.$parent && wrapper.$vnode === wrapper.$parent._vnode) {
        wrapper.$parent.$el = wrapper.$el;
        wrapper = wrapper.$parent;
      }
    };
    Vue2.prototype.$forceUpdate = function() {
      var vm = this;
      if (vm._watcher) {
        vm._watcher.update();
      }
    };
    Vue2.prototype.$destroy = function() {
      var vm = this;
      if (vm._isBeingDestroyed) {
        return;
      }
      callHook$1(vm, "beforeDestroy");
      vm._isBeingDestroyed = true;
      var parent = vm.$parent;
      if (parent && !parent._isBeingDestroyed && !vm.$options.abstract) {
        remove$2(parent.$children, vm);
      }
      vm._scope.stop();
      if (vm._data.__ob__) {
        vm._data.__ob__.vmCount--;
      }
      vm._isDestroyed = true;
      vm.__patch__(vm._vnode, null);
      callHook$1(vm, "destroyed");
      vm.$off();
      if (vm.$el) {
        vm.$el.__vue__ = null;
      }
      if (vm.$vnode) {
        vm.$vnode.parent = null;
      }
    };
  }
  function mountComponent(vm, el, hydrating) {
    vm.$el = el;
    if (!vm.$options.render) {
      vm.$options.render = createEmptyVNode;
    }
    callHook$1(vm, "beforeMount");
    var updateComponent;
    {
      updateComponent = function() {
        vm._update(vm._render(), hydrating);
      };
    }
    var watcherOptions = {
      before: function() {
        if (vm._isMounted && !vm._isDestroyed) {
          callHook$1(vm, "beforeUpdate");
        }
      }
    };
    new Watcher(
      vm,
      updateComponent,
      noop,
      watcherOptions,
      true
      /* isRenderWatcher */
    );
    hydrating = false;
    var preWatchers = vm._preWatchers;
    if (preWatchers) {
      for (var i = 0; i < preWatchers.length; i++) {
        preWatchers[i].run();
      }
    }
    if (vm.$vnode == null) {
      vm._isMounted = true;
      callHook$1(vm, "mounted");
    }
    return vm;
  }
  function updateChildComponent(vm, propsData, listeners, parentVnode, renderChildren) {
    var newScopedSlots = parentVnode.data.scopedSlots;
    var oldScopedSlots = vm.$scopedSlots;
    var hasDynamicScopedSlot = !!(newScopedSlots && !newScopedSlots.$stable || oldScopedSlots !== emptyObject && !oldScopedSlots.$stable || newScopedSlots && vm.$scopedSlots.$key !== newScopedSlots.$key || !newScopedSlots && vm.$scopedSlots.$key);
    var needsForceUpdate = !!(renderChildren || // has new static slots
    vm.$options._renderChildren || // has old static slots
    hasDynamicScopedSlot);
    var prevVNode = vm.$vnode;
    vm.$options._parentVnode = parentVnode;
    vm.$vnode = parentVnode;
    if (vm._vnode) {
      vm._vnode.parent = parentVnode;
    }
    vm.$options._renderChildren = renderChildren;
    var attrs2 = parentVnode.data.attrs || emptyObject;
    if (vm._attrsProxy) {
      if (syncSetupProxy(vm._attrsProxy, attrs2, prevVNode.data && prevVNode.data.attrs || emptyObject, vm, "$attrs")) {
        needsForceUpdate = true;
      }
    }
    vm.$attrs = attrs2;
    listeners = listeners || emptyObject;
    var prevListeners = vm.$options._parentListeners;
    if (vm._listenersProxy) {
      syncSetupProxy(vm._listenersProxy, listeners, prevListeners || emptyObject, vm, "$listeners");
    }
    vm.$listeners = vm.$options._parentListeners = listeners;
    updateComponentListeners(vm, listeners, prevListeners);
    if (propsData && vm.$options.props) {
      toggleObserving(false);
      var props2 = vm._props;
      var propKeys = vm.$options._propKeys || [];
      for (var i = 0; i < propKeys.length; i++) {
        var key = propKeys[i];
        var propOptions = vm.$options.props;
        props2[key] = validateProp(key, propOptions, propsData, vm);
      }
      toggleObserving(true);
      vm.$options.propsData = propsData;
    }
    if (needsForceUpdate) {
      vm.$slots = resolveSlots(renderChildren, parentVnode.context);
      vm.$forceUpdate();
    }
  }
  function isInInactiveTree(vm) {
    while (vm && (vm = vm.$parent)) {
      if (vm._inactive)
        return true;
    }
    return false;
  }
  function activateChildComponent(vm, direct) {
    if (direct) {
      vm._directInactive = false;
      if (isInInactiveTree(vm)) {
        return;
      }
    } else if (vm._directInactive) {
      return;
    }
    if (vm._inactive || vm._inactive === null) {
      vm._inactive = false;
      for (var i = 0; i < vm.$children.length; i++) {
        activateChildComponent(vm.$children[i]);
      }
      callHook$1(vm, "activated");
    }
  }
  function deactivateChildComponent(vm, direct) {
    if (direct) {
      vm._directInactive = true;
      if (isInInactiveTree(vm)) {
        return;
      }
    }
    if (!vm._inactive) {
      vm._inactive = true;
      for (var i = 0; i < vm.$children.length; i++) {
        deactivateChildComponent(vm.$children[i]);
      }
      callHook$1(vm, "deactivated");
    }
  }
  function callHook$1(vm, hook, args, setContext) {
    if (setContext === void 0) {
      setContext = true;
    }
    pushTarget();
    var prevInst = currentInstance;
    var prevScope = getCurrentScope();
    setContext && setCurrentInstance(vm);
    var handlers = vm.$options[hook];
    var info = "".concat(hook, " hook");
    if (handlers) {
      for (var i = 0, j = handlers.length; i < j; i++) {
        invokeWithErrorHandling(handlers[i], vm, null, vm, info);
      }
    }
    if (vm._hasHookEvent) {
      vm.$emit("hook:" + hook);
    }
    if (setContext) {
      setCurrentInstance(prevInst);
      prevScope && prevScope.on();
    }
    popTarget();
  }
  var queue = [];
  var activatedChildren = [];
  var has = {};
  var waiting = false;
  var flushing = false;
  var index = 0;
  function resetSchedulerState() {
    index = queue.length = activatedChildren.length = 0;
    has = {};
    waiting = flushing = false;
  }
  var currentFlushTimestamp = 0;
  var getNow = Date.now;
  if (inBrowser && !isIE) {
    var performance_1 = window.performance;
    if (performance_1 && typeof performance_1.now === "function" && getNow() > document.createEvent("Event").timeStamp) {
      getNow = function() {
        return performance_1.now();
      };
    }
  }
  var sortCompareFn = function(a, b) {
    if (a.post) {
      if (!b.post)
        return 1;
    } else if (b.post) {
      return -1;
    }
    return a.id - b.id;
  };
  function flushSchedulerQueue() {
    currentFlushTimestamp = getNow();
    flushing = true;
    var watcher, id;
    queue.sort(sortCompareFn);
    for (index = 0; index < queue.length; index++) {
      watcher = queue[index];
      if (watcher.before) {
        watcher.before();
      }
      id = watcher.id;
      has[id] = null;
      watcher.run();
    }
    var activatedQueue = activatedChildren.slice();
    var updatedQueue = queue.slice();
    resetSchedulerState();
    callActivatedHooks(activatedQueue);
    callUpdatedHooks(updatedQueue);
    cleanupDeps();
    if (devtools && config.devtools) {
      devtools.emit("flush");
    }
  }
  function callUpdatedHooks(queue2) {
    var i = queue2.length;
    while (i--) {
      var watcher = queue2[i];
      var vm = watcher.vm;
      if (vm && vm._watcher === watcher && vm._isMounted && !vm._isDestroyed) {
        callHook$1(vm, "updated");
      }
    }
  }
  function queueActivatedComponent(vm) {
    vm._inactive = false;
    activatedChildren.push(vm);
  }
  function callActivatedHooks(queue2) {
    for (var i = 0; i < queue2.length; i++) {
      queue2[i]._inactive = true;
      activateChildComponent(
        queue2[i],
        true
        /* true */
      );
    }
  }
  function queueWatcher(watcher) {
    var id = watcher.id;
    if (has[id] != null) {
      return;
    }
    if (watcher === Dep.target && watcher.noRecurse) {
      return;
    }
    has[id] = true;
    if (!flushing) {
      queue.push(watcher);
    } else {
      var i = queue.length - 1;
      while (i > index && queue[i].id > watcher.id) {
        i--;
      }
      queue.splice(i + 1, 0, watcher);
    }
    if (!waiting) {
      waiting = true;
      nextTick(flushSchedulerQueue);
    }
  }
  function initProvide(vm) {
    var provideOption = vm.$options.provide;
    if (provideOption) {
      var provided = isFunction(provideOption) ? provideOption.call(vm) : provideOption;
      if (!isObject(provided)) {
        return;
      }
      var source = resolveProvided(vm);
      var keys = hasSymbol ? Reflect.ownKeys(provided) : Object.keys(provided);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        Object.defineProperty(source, key, Object.getOwnPropertyDescriptor(provided, key));
      }
    }
  }
  function initInjections(vm) {
    var result = resolveInject(vm.$options.inject, vm);
    if (result) {
      toggleObserving(false);
      Object.keys(result).forEach(function(key) {
        {
          defineReactive(vm, key, result[key]);
        }
      });
      toggleObserving(true);
    }
  }
  function resolveInject(inject2, vm) {
    if (inject2) {
      var result = /* @__PURE__ */ Object.create(null);
      var keys = hasSymbol ? Reflect.ownKeys(inject2) : Object.keys(inject2);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (key === "__ob__")
          continue;
        var provideKey = inject2[key].from;
        if (provideKey in vm._provided) {
          result[key] = vm._provided[provideKey];
        } else if ("default" in inject2[key]) {
          var provideDefault = inject2[key].default;
          result[key] = isFunction(provideDefault) ? provideDefault.call(vm) : provideDefault;
        } else ;
      }
      return result;
    }
  }
  function FunctionalRenderContext(data, props2, children, parent, Ctor) {
    var _this = this;
    var options = Ctor.options;
    var contextVm;
    if (hasOwn(parent, "_uid")) {
      contextVm = Object.create(parent);
      contextVm._original = parent;
    } else {
      contextVm = parent;
      parent = parent._original;
    }
    var isCompiled = isTrue(options._compiled);
    var needNormalization = !isCompiled;
    this.data = data;
    this.props = props2;
    this.children = children;
    this.parent = parent;
    this.listeners = data.on || emptyObject;
    this.injections = resolveInject(options.inject, parent);
    this.slots = function() {
      if (!_this.$slots) {
        normalizeScopedSlots(parent, data.scopedSlots, _this.$slots = resolveSlots(children, parent));
      }
      return _this.$slots;
    };
    Object.defineProperty(this, "scopedSlots", {
      enumerable: true,
      get: function() {
        return normalizeScopedSlots(parent, data.scopedSlots, this.slots());
      }
    });
    if (isCompiled) {
      this.$options = options;
      this.$slots = this.slots();
      this.$scopedSlots = normalizeScopedSlots(parent, data.scopedSlots, this.$slots);
    }
    if (options._scopeId) {
      this._c = function(a, b, c, d) {
        var vnode = createElement$1(contextVm, a, b, c, d, needNormalization);
        if (vnode && !isArray(vnode)) {
          vnode.fnScopeId = options._scopeId;
          vnode.fnContext = parent;
        }
        return vnode;
      };
    } else {
      this._c = function(a, b, c, d) {
        return createElement$1(contextVm, a, b, c, d, needNormalization);
      };
    }
  }
  installRenderHelpers(FunctionalRenderContext.prototype);
  function createFunctionalComponent(Ctor, propsData, data, contextVm, children) {
    var options = Ctor.options;
    var props2 = {};
    var propOptions = options.props;
    if (isDef(propOptions)) {
      for (var key in propOptions) {
        props2[key] = validateProp(key, propOptions, propsData || emptyObject);
      }
    } else {
      if (isDef(data.attrs))
        mergeProps(props2, data.attrs);
      if (isDef(data.props))
        mergeProps(props2, data.props);
    }
    var renderContext = new FunctionalRenderContext(data, props2, children, contextVm, Ctor);
    var vnode = options.render.call(null, renderContext._c, renderContext);
    if (vnode instanceof VNode) {
      return cloneAndMarkFunctionalResult(vnode, data, renderContext.parent, options);
    } else if (isArray(vnode)) {
      var vnodes = normalizeChildren(vnode) || [];
      var res = new Array(vnodes.length);
      for (var i = 0; i < vnodes.length; i++) {
        res[i] = cloneAndMarkFunctionalResult(vnodes[i], data, renderContext.parent, options);
      }
      return res;
    }
  }
  function cloneAndMarkFunctionalResult(vnode, data, contextVm, options, renderContext) {
    var clone = cloneVNode(vnode);
    clone.fnContext = contextVm;
    clone.fnOptions = options;
    if (data.slot) {
      (clone.data || (clone.data = {})).slot = data.slot;
    }
    return clone;
  }
  function mergeProps(to, from) {
    for (var key in from) {
      to[camelize(key)] = from[key];
    }
  }
  function getComponentName(options) {
    return options.name || options.__name || options._componentTag;
  }
  var componentVNodeHooks = {
    init: function(vnode, hydrating) {
      if (vnode.componentInstance && !vnode.componentInstance._isDestroyed && vnode.data.keepAlive) {
        var mountedNode = vnode;
        componentVNodeHooks.prepatch(mountedNode, mountedNode);
      } else {
        var child = vnode.componentInstance = createComponentInstanceForVnode(vnode, activeInstance);
        child.$mount(hydrating ? vnode.elm : void 0, hydrating);
      }
    },
    prepatch: function(oldVnode, vnode) {
      var options = vnode.componentOptions;
      var child = vnode.componentInstance = oldVnode.componentInstance;
      updateChildComponent(
        child,
        options.propsData,
        // updated props
        options.listeners,
        // updated listeners
        vnode,
        // new parent vnode
        options.children
        // new children
      );
    },
    insert: function(vnode) {
      var context = vnode.context, componentInstance = vnode.componentInstance;
      if (!componentInstance._isMounted) {
        componentInstance._isMounted = true;
        callHook$1(componentInstance, "mounted");
      }
      if (vnode.data.keepAlive) {
        if (context._isMounted) {
          queueActivatedComponent(componentInstance);
        } else {
          activateChildComponent(
            componentInstance,
            true
            /* direct */
          );
        }
      }
    },
    destroy: function(vnode) {
      var componentInstance = vnode.componentInstance;
      if (!componentInstance._isDestroyed) {
        if (!vnode.data.keepAlive) {
          componentInstance.$destroy();
        } else {
          deactivateChildComponent(
            componentInstance,
            true
            /* direct */
          );
        }
      }
    }
  };
  var hooksToMerge = Object.keys(componentVNodeHooks);
  function createComponent(Ctor, data, context, children, tag) {
    if (isUndef(Ctor)) {
      return;
    }
    var baseCtor = context.$options._base;
    if (isObject(Ctor)) {
      Ctor = baseCtor.extend(Ctor);
    }
    if (typeof Ctor !== "function") {
      return;
    }
    var asyncFactory;
    if (isUndef(Ctor.cid)) {
      asyncFactory = Ctor;
      Ctor = resolveAsyncComponent(asyncFactory, baseCtor);
      if (Ctor === void 0) {
        return createAsyncPlaceholder(asyncFactory, data, context, children, tag);
      }
    }
    data = data || {};
    resolveConstructorOptions(Ctor);
    if (isDef(data.model)) {
      transformModel(Ctor.options, data);
    }
    var propsData = extractPropsFromVNodeData(data, Ctor);
    if (isTrue(Ctor.options.functional)) {
      return createFunctionalComponent(Ctor, propsData, data, context, children);
    }
    var listeners = data.on;
    data.on = data.nativeOn;
    if (isTrue(Ctor.options.abstract)) {
      var slot = data.slot;
      data = {};
      if (slot) {
        data.slot = slot;
      }
    }
    installComponentHooks(data);
    var name = getComponentName(Ctor.options) || tag;
    var vnode = new VNode(
      // @ts-expect-error
      "vue-component-".concat(Ctor.cid).concat(name ? "-".concat(name) : ""),
      data,
      void 0,
      void 0,
      void 0,
      context,
      // @ts-expect-error
      { Ctor, propsData, listeners, tag, children },
      asyncFactory
    );
    return vnode;
  }
  function createComponentInstanceForVnode(vnode, parent) {
    var options = {
      _isComponent: true,
      _parentVnode: vnode,
      parent
    };
    var inlineTemplate = vnode.data.inlineTemplate;
    if (isDef(inlineTemplate)) {
      options.render = inlineTemplate.render;
      options.staticRenderFns = inlineTemplate.staticRenderFns;
    }
    return new vnode.componentOptions.Ctor(options);
  }
  function installComponentHooks(data) {
    var hooks2 = data.hook || (data.hook = {});
    for (var i = 0; i < hooksToMerge.length; i++) {
      var key = hooksToMerge[i];
      var existing = hooks2[key];
      var toMerge = componentVNodeHooks[key];
      if (existing !== toMerge && !(existing && existing._merged)) {
        hooks2[key] = existing ? mergeHook(toMerge, existing) : toMerge;
      }
    }
  }
  function mergeHook(f1, f2) {
    var merged = function(a, b) {
      f1(a, b);
      f2(a, b);
    };
    merged._merged = true;
    return merged;
  }
  function transformModel(options, data) {
    var prop = options.model && options.model.prop || "value";
    var event = options.model && options.model.event || "input";
    (data.attrs || (data.attrs = {}))[prop] = data.model.value;
    var on = data.on || (data.on = {});
    var existing = on[event];
    var callback = data.model.callback;
    if (isDef(existing)) {
      if (isArray(existing) ? existing.indexOf(callback) === -1 : existing !== callback) {
        on[event] = [callback].concat(existing);
      }
    } else {
      on[event] = callback;
    }
  }
  var warn = noop;
  var strats = config.optionMergeStrategies;
  function mergeData(to, from, recursive) {
    if (recursive === void 0) {
      recursive = true;
    }
    if (!from)
      return to;
    var key, toVal, fromVal;
    var keys = hasSymbol ? Reflect.ownKeys(from) : Object.keys(from);
    for (var i = 0; i < keys.length; i++) {
      key = keys[i];
      if (key === "__ob__")
        continue;
      toVal = to[key];
      fromVal = from[key];
      if (!recursive || !hasOwn(to, key)) {
        set(to, key, fromVal);
      } else if (toVal !== fromVal && isPlainObject(toVal) && isPlainObject(fromVal)) {
        mergeData(toVal, fromVal);
      }
    }
    return to;
  }
  function mergeDataOrFn(parentVal, childVal, vm) {
    if (!vm) {
      if (!childVal) {
        return parentVal;
      }
      if (!parentVal) {
        return childVal;
      }
      return function mergedDataFn() {
        return mergeData(isFunction(childVal) ? childVal.call(this, this) : childVal, isFunction(parentVal) ? parentVal.call(this, this) : parentVal);
      };
    } else {
      return function mergedInstanceDataFn() {
        var instanceData = isFunction(childVal) ? childVal.call(vm, vm) : childVal;
        var defaultData = isFunction(parentVal) ? parentVal.call(vm, vm) : parentVal;
        if (instanceData) {
          return mergeData(instanceData, defaultData);
        } else {
          return defaultData;
        }
      };
    }
  }
  strats.data = function(parentVal, childVal, vm) {
    if (!vm) {
      if (childVal && typeof childVal !== "function") {
        return parentVal;
      }
      return mergeDataOrFn(parentVal, childVal);
    }
    return mergeDataOrFn(parentVal, childVal, vm);
  };
  function mergeLifecycleHook(parentVal, childVal) {
    var res = childVal ? parentVal ? parentVal.concat(childVal) : isArray(childVal) ? childVal : [childVal] : parentVal;
    return res ? dedupeHooks(res) : res;
  }
  function dedupeHooks(hooks2) {
    var res = [];
    for (var i = 0; i < hooks2.length; i++) {
      if (res.indexOf(hooks2[i]) === -1) {
        res.push(hooks2[i]);
      }
    }
    return res;
  }
  LIFECYCLE_HOOKS.forEach(function(hook) {
    strats[hook] = mergeLifecycleHook;
  });
  function mergeAssets(parentVal, childVal, vm, key) {
    var res = Object.create(parentVal || null);
    if (childVal) {
      return extend(res, childVal);
    } else {
      return res;
    }
  }
  ASSET_TYPES.forEach(function(type) {
    strats[type + "s"] = mergeAssets;
  });
  strats.watch = function(parentVal, childVal, vm, key) {
    if (parentVal === nativeWatch)
      parentVal = void 0;
    if (childVal === nativeWatch)
      childVal = void 0;
    if (!childVal)
      return Object.create(parentVal || null);
    if (!parentVal)
      return childVal;
    var ret = {};
    extend(ret, parentVal);
    for (var key_1 in childVal) {
      var parent_1 = ret[key_1];
      var child = childVal[key_1];
      if (parent_1 && !isArray(parent_1)) {
        parent_1 = [parent_1];
      }
      ret[key_1] = parent_1 ? parent_1.concat(child) : isArray(child) ? child : [child];
    }
    return ret;
  };
  strats.props = strats.methods = strats.inject = strats.computed = function(parentVal, childVal, vm, key) {
    if (!parentVal)
      return childVal;
    var ret = /* @__PURE__ */ Object.create(null);
    extend(ret, parentVal);
    if (childVal)
      extend(ret, childVal);
    return ret;
  };
  strats.provide = function(parentVal, childVal) {
    if (!parentVal)
      return childVal;
    return function() {
      var ret = /* @__PURE__ */ Object.create(null);
      mergeData(ret, isFunction(parentVal) ? parentVal.call(this) : parentVal);
      if (childVal) {
        mergeData(
          ret,
          isFunction(childVal) ? childVal.call(this) : childVal,
          false
          // non-recursive
        );
      }
      return ret;
    };
  };
  var defaultStrat = function(parentVal, childVal) {
    return childVal === void 0 ? parentVal : childVal;
  };
  function normalizeProps(options, vm) {
    var props2 = options.props;
    if (!props2)
      return;
    var res = {};
    var i, val, name;
    if (isArray(props2)) {
      i = props2.length;
      while (i--) {
        val = props2[i];
        if (typeof val === "string") {
          name = camelize(val);
          res[name] = { type: null };
        }
      }
    } else if (isPlainObject(props2)) {
      for (var key in props2) {
        val = props2[key];
        name = camelize(key);
        res[name] = isPlainObject(val) ? val : { type: val };
      }
    } else ;
    options.props = res;
  }
  function normalizeInject(options, vm) {
    var inject2 = options.inject;
    if (!inject2)
      return;
    var normalized = options.inject = {};
    if (isArray(inject2)) {
      for (var i = 0; i < inject2.length; i++) {
        normalized[inject2[i]] = { from: inject2[i] };
      }
    } else if (isPlainObject(inject2)) {
      for (var key in inject2) {
        var val = inject2[key];
        normalized[key] = isPlainObject(val) ? extend({ from: key }, val) : { from: val };
      }
    } else ;
  }
  function normalizeDirectives$1(options) {
    var dirs = options.directives;
    if (dirs) {
      for (var key in dirs) {
        var def2 = dirs[key];
        if (isFunction(def2)) {
          dirs[key] = { bind: def2, update: def2 };
        }
      }
    }
  }
  function mergeOptions(parent, child, vm) {
    if (isFunction(child)) {
      child = child.options;
    }
    normalizeProps(child);
    normalizeInject(child);
    normalizeDirectives$1(child);
    if (!child._base) {
      if (child.extends) {
        parent = mergeOptions(parent, child.extends, vm);
      }
      if (child.mixins) {
        for (var i = 0, l = child.mixins.length; i < l; i++) {
          parent = mergeOptions(parent, child.mixins[i], vm);
        }
      }
    }
    var options = {};
    var key;
    for (key in parent) {
      mergeField(key);
    }
    for (key in child) {
      if (!hasOwn(parent, key)) {
        mergeField(key);
      }
    }
    function mergeField(key2) {
      var strat = strats[key2] || defaultStrat;
      options[key2] = strat(parent[key2], child[key2], vm, key2);
    }
    return options;
  }
  function resolveAsset(options, type, id, warnMissing) {
    if (typeof id !== "string") {
      return;
    }
    var assets = options[type];
    if (hasOwn(assets, id))
      return assets[id];
    var camelizedId = camelize(id);
    if (hasOwn(assets, camelizedId))
      return assets[camelizedId];
    var PascalCaseId = capitalize(camelizedId);
    if (hasOwn(assets, PascalCaseId))
      return assets[PascalCaseId];
    var res = assets[id] || assets[camelizedId] || assets[PascalCaseId];
    return res;
  }
  function validateProp(key, propOptions, propsData, vm) {
    var prop = propOptions[key];
    var absent = !hasOwn(propsData, key);
    var value = propsData[key];
    var booleanIndex = getTypeIndex(Boolean, prop.type);
    if (booleanIndex > -1) {
      if (absent && !hasOwn(prop, "default")) {
        value = false;
      } else if (value === "" || value === hyphenate(key)) {
        var stringIndex = getTypeIndex(String, prop.type);
        if (stringIndex < 0 || booleanIndex < stringIndex) {
          value = true;
        }
      }
    }
    if (value === void 0) {
      value = getPropDefaultValue(vm, prop, key);
      var prevShouldObserve = shouldObserve;
      toggleObserving(true);
      observe(value);
      toggleObserving(prevShouldObserve);
    }
    return value;
  }
  function getPropDefaultValue(vm, prop, key) {
    if (!hasOwn(prop, "default")) {
      return void 0;
    }
    var def2 = prop.default;
    if (vm && vm.$options.propsData && vm.$options.propsData[key] === void 0 && vm._props[key] !== void 0) {
      return vm._props[key];
    }
    return isFunction(def2) && getType(prop.type) !== "Function" ? def2.call(vm) : def2;
  }
  var functionTypeCheckRE = /^\s*function (\w+)/;
  function getType(fn) {
    var match = fn && fn.toString().match(functionTypeCheckRE);
    return match ? match[1] : "";
  }
  function isSameType(a, b) {
    return getType(a) === getType(b);
  }
  function getTypeIndex(type, expectedTypes) {
    if (!isArray(expectedTypes)) {
      return isSameType(expectedTypes, type) ? 0 : -1;
    }
    for (var i = 0, len = expectedTypes.length; i < len; i++) {
      if (isSameType(expectedTypes[i], type)) {
        return i;
      }
    }
    return -1;
  }
  var sharedPropertyDefinition = {
    enumerable: true,
    configurable: true,
    get: noop,
    set: noop
  };
  function proxy(target2, sourceKey, key) {
    sharedPropertyDefinition.get = function proxyGetter() {
      return this[sourceKey][key];
    };
    sharedPropertyDefinition.set = function proxySetter(val) {
      this[sourceKey][key] = val;
    };
    Object.defineProperty(target2, key, sharedPropertyDefinition);
  }
  function initState(vm) {
    var opts2 = vm.$options;
    if (opts2.props)
      initProps$1(vm, opts2.props);
    initSetup(vm);
    if (opts2.methods)
      initMethods(vm, opts2.methods);
    if (opts2.data) {
      initData(vm);
    } else {
      var ob = observe(vm._data = {});
      ob && ob.vmCount++;
    }
    if (opts2.computed)
      initComputed$1(vm, opts2.computed);
    if (opts2.watch && opts2.watch !== nativeWatch) {
      initWatch(vm, opts2.watch);
    }
  }
  function initProps$1(vm, propsOptions) {
    var propsData = vm.$options.propsData || {};
    var props2 = vm._props = shallowReactive({});
    var keys = vm.$options._propKeys = [];
    var isRoot = !vm.$parent;
    if (!isRoot) {
      toggleObserving(false);
    }
    var _loop_1 = function(key2) {
      keys.push(key2);
      var value = validateProp(key2, propsOptions, propsData, vm);
      {
        defineReactive(
          props2,
          key2,
          value,
          void 0,
          true
          /* shallow */
        );
      }
      if (!(key2 in vm)) {
        proxy(vm, "_props", key2);
      }
    };
    for (var key in propsOptions) {
      _loop_1(key);
    }
    toggleObserving(true);
  }
  function initData(vm) {
    var data = vm.$options.data;
    data = vm._data = isFunction(data) ? getData(data, vm) : data || {};
    if (!isPlainObject(data)) {
      data = {};
    }
    var keys = Object.keys(data);
    var props2 = vm.$options.props;
    vm.$options.methods;
    var i = keys.length;
    while (i--) {
      var key = keys[i];
      if (props2 && hasOwn(props2, key)) ;
      else if (!isReserved(key)) {
        proxy(vm, "_data", key);
      }
    }
    var ob = observe(data);
    ob && ob.vmCount++;
  }
  function getData(data, vm) {
    pushTarget();
    try {
      return data.call(vm, vm);
    } catch (e) {
      handleError(e, vm, "data()");
      return {};
    } finally {
      popTarget();
    }
  }
  var computedWatcherOptions = { lazy: true };
  function initComputed$1(vm, computed2) {
    var watchers = vm._computedWatchers = /* @__PURE__ */ Object.create(null);
    var isSSR = isServerRendering();
    for (var key in computed2) {
      var userDef = computed2[key];
      var getter = isFunction(userDef) ? userDef : userDef.get;
      if (!isSSR) {
        watchers[key] = new Watcher(vm, getter || noop, noop, computedWatcherOptions);
      }
      if (!(key in vm)) {
        defineComputed(vm, key, userDef);
      }
    }
  }
  function defineComputed(target2, key, userDef) {
    var shouldCache = !isServerRendering();
    if (isFunction(userDef)) {
      sharedPropertyDefinition.get = shouldCache ? createComputedGetter(key) : createGetterInvoker(userDef);
      sharedPropertyDefinition.set = noop;
    } else {
      sharedPropertyDefinition.get = userDef.get ? shouldCache && userDef.cache !== false ? createComputedGetter(key) : createGetterInvoker(userDef.get) : noop;
      sharedPropertyDefinition.set = userDef.set || noop;
    }
    Object.defineProperty(target2, key, sharedPropertyDefinition);
  }
  function createComputedGetter(key) {
    return function computedGetter() {
      var watcher = this._computedWatchers && this._computedWatchers[key];
      if (watcher) {
        if (watcher.dirty) {
          watcher.evaluate();
        }
        if (Dep.target) {
          watcher.depend();
        }
        return watcher.value;
      }
    };
  }
  function createGetterInvoker(fn) {
    return function computedGetter() {
      return fn.call(this, this);
    };
  }
  function initMethods(vm, methods) {
    vm.$options.props;
    for (var key in methods) {
      vm[key] = typeof methods[key] !== "function" ? noop : bind(methods[key], vm);
    }
  }
  function initWatch(vm, watch2) {
    for (var key in watch2) {
      var handler = watch2[key];
      if (isArray(handler)) {
        for (var i = 0; i < handler.length; i++) {
          createWatcher(vm, key, handler[i]);
        }
      } else {
        createWatcher(vm, key, handler);
      }
    }
  }
  function createWatcher(vm, expOrFn, handler, options) {
    if (isPlainObject(handler)) {
      options = handler;
      handler = handler.handler;
    }
    if (typeof handler === "string") {
      handler = vm[handler];
    }
    return vm.$watch(expOrFn, handler, options);
  }
  function stateMixin(Vue2) {
    var dataDef = {};
    dataDef.get = function() {
      return this._data;
    };
    var propsDef = {};
    propsDef.get = function() {
      return this._props;
    };
    Object.defineProperty(Vue2.prototype, "$data", dataDef);
    Object.defineProperty(Vue2.prototype, "$props", propsDef);
    Vue2.prototype.$set = set;
    Vue2.prototype.$delete = del;
    Vue2.prototype.$watch = function(expOrFn, cb, options) {
      var vm = this;
      if (isPlainObject(cb)) {
        return createWatcher(vm, expOrFn, cb, options);
      }
      options = options || {};
      options.user = true;
      var watcher = new Watcher(vm, expOrFn, cb, options);
      if (options.immediate) {
        var info = 'callback for immediate watcher "'.concat(watcher.expression, '"');
        pushTarget();
        invokeWithErrorHandling(cb, vm, [watcher.value], vm, info);
        popTarget();
      }
      return function unwatchFn() {
        watcher.teardown();
      };
    };
  }
  var uid = 0;
  function initMixin$1(Vue2) {
    Vue2.prototype._init = function(options) {
      var vm = this;
      vm._uid = uid++;
      vm._isVue = true;
      vm.__v_skip = true;
      vm._scope = new EffectScope(
        true
        /* detached */
      );
      vm._scope.parent = void 0;
      vm._scope._vm = true;
      if (options && options._isComponent) {
        initInternalComponent(vm, options);
      } else {
        vm.$options = mergeOptions(resolveConstructorOptions(vm.constructor), options || {}, vm);
      }
      {
        vm._renderProxy = vm;
      }
      vm._self = vm;
      initLifecycle(vm);
      initEvents(vm);
      initRender(vm);
      callHook$1(
        vm,
        "beforeCreate",
        void 0,
        false
        /* setContext */
      );
      initInjections(vm);
      initState(vm);
      initProvide(vm);
      callHook$1(vm, "created");
      if (vm.$options.el) {
        vm.$mount(vm.$options.el);
      }
    };
  }
  function initInternalComponent(vm, options) {
    var opts2 = vm.$options = Object.create(vm.constructor.options);
    var parentVnode = options._parentVnode;
    opts2.parent = options.parent;
    opts2._parentVnode = parentVnode;
    var vnodeComponentOptions = parentVnode.componentOptions;
    opts2.propsData = vnodeComponentOptions.propsData;
    opts2._parentListeners = vnodeComponentOptions.listeners;
    opts2._renderChildren = vnodeComponentOptions.children;
    opts2._componentTag = vnodeComponentOptions.tag;
    if (options.render) {
      opts2.render = options.render;
      opts2.staticRenderFns = options.staticRenderFns;
    }
  }
  function resolveConstructorOptions(Ctor) {
    var options = Ctor.options;
    if (Ctor.super) {
      var superOptions = resolveConstructorOptions(Ctor.super);
      var cachedSuperOptions = Ctor.superOptions;
      if (superOptions !== cachedSuperOptions) {
        Ctor.superOptions = superOptions;
        var modifiedOptions = resolveModifiedOptions(Ctor);
        if (modifiedOptions) {
          extend(Ctor.extendOptions, modifiedOptions);
        }
        options = Ctor.options = mergeOptions(superOptions, Ctor.extendOptions);
        if (options.name) {
          options.components[options.name] = Ctor;
        }
      }
    }
    return options;
  }
  function resolveModifiedOptions(Ctor) {
    var modified;
    var latest = Ctor.options;
    var sealed = Ctor.sealedOptions;
    for (var key in latest) {
      if (latest[key] !== sealed[key]) {
        if (!modified)
          modified = {};
        modified[key] = latest[key];
      }
    }
    return modified;
  }
  function Vue(options) {
    this._init(options);
  }
  initMixin$1(Vue);
  stateMixin(Vue);
  eventsMixin(Vue);
  lifecycleMixin(Vue);
  renderMixin(Vue);
  function initUse(Vue2) {
    Vue2.use = function(plugin) {
      var installedPlugins = this._installedPlugins || (this._installedPlugins = []);
      if (installedPlugins.indexOf(plugin) > -1) {
        return this;
      }
      var args = toArray(arguments, 1);
      args.unshift(this);
      if (isFunction(plugin.install)) {
        plugin.install.apply(plugin, args);
      } else if (isFunction(plugin)) {
        plugin.apply(null, args);
      }
      installedPlugins.push(plugin);
      return this;
    };
  }
  function initMixin(Vue2) {
    Vue2.mixin = function(mixin) {
      this.options = mergeOptions(this.options, mixin);
      return this;
    };
  }
  function initExtend(Vue2) {
    Vue2.cid = 0;
    var cid = 1;
    Vue2.extend = function(extendOptions) {
      extendOptions = extendOptions || {};
      var Super = this;
      var SuperId = Super.cid;
      var cachedCtors = extendOptions._Ctor || (extendOptions._Ctor = {});
      if (cachedCtors[SuperId]) {
        return cachedCtors[SuperId];
      }
      var name = getComponentName(extendOptions) || getComponentName(Super.options);
      var Sub = function VueComponent(options) {
        this._init(options);
      };
      Sub.prototype = Object.create(Super.prototype);
      Sub.prototype.constructor = Sub;
      Sub.cid = cid++;
      Sub.options = mergeOptions(Super.options, extendOptions);
      Sub["super"] = Super;
      if (Sub.options.props) {
        initProps(Sub);
      }
      if (Sub.options.computed) {
        initComputed(Sub);
      }
      Sub.extend = Super.extend;
      Sub.mixin = Super.mixin;
      Sub.use = Super.use;
      ASSET_TYPES.forEach(function(type) {
        Sub[type] = Super[type];
      });
      if (name) {
        Sub.options.components[name] = Sub;
      }
      Sub.superOptions = Super.options;
      Sub.extendOptions = extendOptions;
      Sub.sealedOptions = extend({}, Sub.options);
      cachedCtors[SuperId] = Sub;
      return Sub;
    };
  }
  function initProps(Comp) {
    var props2 = Comp.options.props;
    for (var key in props2) {
      proxy(Comp.prototype, "_props", key);
    }
  }
  function initComputed(Comp) {
    var computed2 = Comp.options.computed;
    for (var key in computed2) {
      defineComputed(Comp.prototype, key, computed2[key]);
    }
  }
  function initAssetRegisters(Vue2) {
    ASSET_TYPES.forEach(function(type) {
      Vue2[type] = function(id, definition) {
        if (!definition) {
          return this.options[type + "s"][id];
        } else {
          if (type === "component" && isPlainObject(definition)) {
            definition.name = definition.name || id;
            definition = this.options._base.extend(definition);
          }
          if (type === "directive" && isFunction(definition)) {
            definition = { bind: definition, update: definition };
          }
          this.options[type + "s"][id] = definition;
          return definition;
        }
      };
    });
  }
  function _getComponentName(opts2) {
    return opts2 && (getComponentName(opts2.Ctor.options) || opts2.tag);
  }
  function matches(pattern, name) {
    if (isArray(pattern)) {
      return pattern.indexOf(name) > -1;
    } else if (typeof pattern === "string") {
      return pattern.split(",").indexOf(name) > -1;
    } else if (isRegExp(pattern)) {
      return pattern.test(name);
    }
    return false;
  }
  function pruneCache(keepAliveInstance, filter) {
    var cache = keepAliveInstance.cache, keys = keepAliveInstance.keys, _vnode = keepAliveInstance._vnode, $vnode = keepAliveInstance.$vnode;
    for (var key in cache) {
      var entry = cache[key];
      if (entry) {
        var name_1 = entry.name;
        if (name_1 && !filter(name_1)) {
          pruneCacheEntry(cache, key, keys, _vnode);
        }
      }
    }
    $vnode.componentOptions.children = void 0;
  }
  function pruneCacheEntry(cache, key, keys, current) {
    var entry = cache[key];
    if (entry && (!current || entry.tag !== current.tag)) {
      entry.componentInstance.$destroy();
    }
    cache[key] = null;
    remove$2(keys, key);
  }
  var patternTypes = [String, RegExp, Array];
  var KeepAlive = {
    name: "keep-alive",
    abstract: true,
    props: {
      include: patternTypes,
      exclude: patternTypes,
      max: [String, Number]
    },
    methods: {
      cacheVNode: function() {
        var _a = this, cache = _a.cache, keys = _a.keys, vnodeToCache = _a.vnodeToCache, keyToCache = _a.keyToCache;
        if (vnodeToCache) {
          var tag = vnodeToCache.tag, componentInstance = vnodeToCache.componentInstance, componentOptions = vnodeToCache.componentOptions;
          cache[keyToCache] = {
            name: _getComponentName(componentOptions),
            tag,
            componentInstance
          };
          keys.push(keyToCache);
          if (this.max && keys.length > parseInt(this.max)) {
            pruneCacheEntry(cache, keys[0], keys, this._vnode);
          }
          this.vnodeToCache = null;
        }
      }
    },
    created: function() {
      this.cache = /* @__PURE__ */ Object.create(null);
      this.keys = [];
    },
    destroyed: function() {
      for (var key in this.cache) {
        pruneCacheEntry(this.cache, key, this.keys);
      }
    },
    mounted: function() {
      var _this = this;
      this.cacheVNode();
      this.$watch("include", function(val) {
        pruneCache(_this, function(name) {
          return matches(val, name);
        });
      });
      this.$watch("exclude", function(val) {
        pruneCache(_this, function(name) {
          return !matches(val, name);
        });
      });
    },
    updated: function() {
      this.cacheVNode();
    },
    render: function() {
      var slot = this.$slots.default;
      var vnode = getFirstComponentChild(slot);
      var componentOptions = vnode && vnode.componentOptions;
      if (componentOptions) {
        var name_2 = _getComponentName(componentOptions);
        var _a = this, include = _a.include, exclude = _a.exclude;
        if (
          // not included
          include && (!name_2 || !matches(include, name_2)) || // excluded
          exclude && name_2 && matches(exclude, name_2)
        ) {
          return vnode;
        }
        var _b = this, cache = _b.cache, keys = _b.keys;
        var key = vnode.key == null ? (
          // same constructor may get registered as different local components
          // so cid alone is not enough (#3269)
          componentOptions.Ctor.cid + (componentOptions.tag ? "::".concat(componentOptions.tag) : "")
        ) : vnode.key;
        if (cache[key]) {
          vnode.componentInstance = cache[key].componentInstance;
          remove$2(keys, key);
          keys.push(key);
        } else {
          this.vnodeToCache = vnode;
          this.keyToCache = key;
        }
        vnode.data.keepAlive = true;
      }
      return vnode || slot && slot[0];
    }
  };
  var builtInComponents = {
    KeepAlive
  };
  function initGlobalAPI(Vue2) {
    var configDef = {};
    configDef.get = function() {
      return config;
    };
    Object.defineProperty(Vue2, "config", configDef);
    Vue2.util = {
      warn,
      extend,
      mergeOptions,
      defineReactive
    };
    Vue2.set = set;
    Vue2.delete = del;
    Vue2.nextTick = nextTick;
    Vue2.observable = function(obj) {
      observe(obj);
      return obj;
    };
    Vue2.options = /* @__PURE__ */ Object.create(null);
    ASSET_TYPES.forEach(function(type) {
      Vue2.options[type + "s"] = /* @__PURE__ */ Object.create(null);
    });
    Vue2.options._base = Vue2;
    extend(Vue2.options.components, builtInComponents);
    initUse(Vue2);
    initMixin(Vue2);
    initExtend(Vue2);
    initAssetRegisters(Vue2);
  }
  initGlobalAPI(Vue);
  Object.defineProperty(Vue.prototype, "$isServer", {
    get: isServerRendering
  });
  Object.defineProperty(Vue.prototype, "$ssrContext", {
    get: function() {
      return this.$vnode && this.$vnode.ssrContext;
    }
  });
  Object.defineProperty(Vue, "FunctionalRenderContext", {
    value: FunctionalRenderContext
  });
  Vue.version = version;
  var isReservedAttr = makeMap("style,class");
  var acceptValue = makeMap("input,textarea,option,select,progress");
  var mustUseProp = function(tag, type, attr) {
    return attr === "value" && acceptValue(tag) && type !== "button" || attr === "selected" && tag === "option" || attr === "checked" && tag === "input" || attr === "muted" && tag === "video";
  };
  var isEnumeratedAttr = makeMap("contenteditable,draggable,spellcheck");
  var isValidContentEditableValue = makeMap("events,caret,typing,plaintext-only");
  var convertEnumeratedValue = function(key, value) {
    return isFalsyAttrValue(value) || value === "false" ? "false" : (
      // allow arbitrary string value for contenteditable
      key === "contenteditable" && isValidContentEditableValue(value) ? value : "true"
    );
  };
  var isBooleanAttr = makeMap("allowfullscreen,async,autofocus,autoplay,checked,compact,controls,declare,default,defaultchecked,defaultmuted,defaultselected,defer,disabled,enabled,formnovalidate,hidden,indeterminate,inert,ismap,itemscope,loop,multiple,muted,nohref,noresize,noshade,novalidate,nowrap,open,pauseonexit,readonly,required,reversed,scoped,seamless,selected,sortable,truespeed,typemustmatch,visible");
  var xlinkNS = "http://www.w3.org/1999/xlink";
  var isXlink = function(name) {
    return name.charAt(5) === ":" && name.slice(0, 5) === "xlink";
  };
  var getXlinkProp = function(name) {
    return isXlink(name) ? name.slice(6, name.length) : "";
  };
  var isFalsyAttrValue = function(val) {
    return val == null || val === false;
  };
  function genClassForVnode(vnode) {
    var data = vnode.data;
    var parentNode2 = vnode;
    var childNode = vnode;
    while (isDef(childNode.componentInstance)) {
      childNode = childNode.componentInstance._vnode;
      if (childNode && childNode.data) {
        data = mergeClassData(childNode.data, data);
      }
    }
    while (isDef(parentNode2 = parentNode2.parent)) {
      if (parentNode2 && parentNode2.data) {
        data = mergeClassData(data, parentNode2.data);
      }
    }
    return renderClass(data.staticClass, data.class);
  }
  function mergeClassData(child, parent) {
    return {
      staticClass: concat(child.staticClass, parent.staticClass),
      class: isDef(child.class) ? [child.class, parent.class] : parent.class
    };
  }
  function renderClass(staticClass, dynamicClass) {
    if (isDef(staticClass) || isDef(dynamicClass)) {
      return concat(staticClass, stringifyClass(dynamicClass));
    }
    return "";
  }
  function concat(a, b) {
    return a ? b ? a + " " + b : a : b || "";
  }
  function stringifyClass(value) {
    if (Array.isArray(value)) {
      return stringifyArray(value);
    }
    if (isObject(value)) {
      return stringifyObject(value);
    }
    if (typeof value === "string") {
      return value;
    }
    return "";
  }
  function stringifyArray(value) {
    var res = "";
    var stringified;
    for (var i = 0, l = value.length; i < l; i++) {
      if (isDef(stringified = stringifyClass(value[i])) && stringified !== "") {
        if (res)
          res += " ";
        res += stringified;
      }
    }
    return res;
  }
  function stringifyObject(value) {
    var res = "";
    for (var key in value) {
      if (value[key]) {
        if (res)
          res += " ";
        res += key;
      }
    }
    return res;
  }
  var namespaceMap = {
    svg: "http://www.w3.org/2000/svg",
    math: "http://www.w3.org/1998/Math/MathML"
  };
  var isHTMLTag = makeMap("html,body,base,head,link,meta,style,title,address,article,aside,footer,header,h1,h2,h3,h4,h5,h6,hgroup,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,rtc,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,menuitem,summary,content,element,shadow,template,blockquote,iframe,tfoot");
  var isSVG = makeMap("svg,animate,circle,clippath,cursor,defs,desc,ellipse,filter,font-face,foreignobject,g,glyph,image,line,marker,mask,missing-glyph,path,pattern,polygon,polyline,rect,switch,symbol,text,textpath,tspan,use,view", true);
  var isReservedTag = function(tag) {
    return isHTMLTag(tag) || isSVG(tag);
  };
  function getTagNamespace(tag) {
    if (isSVG(tag)) {
      return "svg";
    }
    if (tag === "math") {
      return "math";
    }
  }
  var unknownElementCache = /* @__PURE__ */ Object.create(null);
  function isUnknownElement(tag) {
    if (!inBrowser) {
      return true;
    }
    if (isReservedTag(tag)) {
      return false;
    }
    tag = tag.toLowerCase();
    if (unknownElementCache[tag] != null) {
      return unknownElementCache[tag];
    }
    var el = document.createElement(tag);
    if (tag.indexOf("-") > -1) {
      return unknownElementCache[tag] = el.constructor === window.HTMLUnknownElement || el.constructor === window.HTMLElement;
    } else {
      return unknownElementCache[tag] = /HTMLUnknownElement/.test(el.toString());
    }
  }
  var isTextInputType = makeMap("text,number,password,search,email,tel,url");
  function query(el) {
    if (typeof el === "string") {
      var selected = document.querySelector(el);
      if (!selected) {
        return document.createElement("div");
      }
      return selected;
    } else {
      return el;
    }
  }
  function createElement(tagName2, vnode) {
    var elm = document.createElement(tagName2);
    if (tagName2 !== "select") {
      return elm;
    }
    if (vnode.data && vnode.data.attrs && vnode.data.attrs.multiple !== void 0) {
      elm.setAttribute("multiple", "multiple");
    }
    return elm;
  }
  function createElementNS(namespace, tagName2) {
    return document.createElementNS(namespaceMap[namespace], tagName2);
  }
  function createTextNode(text) {
    return document.createTextNode(text);
  }
  function createComment(text) {
    return document.createComment(text);
  }
  function insertBefore(parentNode2, newNode, referenceNode) {
    parentNode2.insertBefore(newNode, referenceNode);
  }
  function removeChild(node, child) {
    node.removeChild(child);
  }
  function appendChild(node, child) {
    node.appendChild(child);
  }
  function parentNode(node) {
    return node.parentNode;
  }
  function nextSibling(node) {
    return node.nextSibling;
  }
  function tagName(node) {
    return node.tagName;
  }
  function setTextContent(node, text) {
    node.textContent = text;
  }
  function setStyleScope(node, scopeId) {
    node.setAttribute(scopeId, "");
  }
  var nodeOps = /* @__PURE__ */ Object.freeze({
    __proto__: null,
    createElement,
    createElementNS,
    createTextNode,
    createComment,
    insertBefore,
    removeChild,
    appendChild,
    parentNode,
    nextSibling,
    tagName,
    setTextContent,
    setStyleScope
  });
  var ref = {
    create: function(_, vnode) {
      registerRef(vnode);
    },
    update: function(oldVnode, vnode) {
      if (oldVnode.data.ref !== vnode.data.ref) {
        registerRef(oldVnode, true);
        registerRef(vnode);
      }
    },
    destroy: function(vnode) {
      registerRef(vnode, true);
    }
  };
  function registerRef(vnode, isRemoval) {
    var ref2 = vnode.data.ref;
    if (!isDef(ref2))
      return;
    var vm = vnode.context;
    var refValue = vnode.componentInstance || vnode.elm;
    var value = isRemoval ? null : refValue;
    var $refsValue = isRemoval ? void 0 : refValue;
    if (isFunction(ref2)) {
      invokeWithErrorHandling(ref2, vm, [value], vm, "template ref function");
      return;
    }
    var isFor = vnode.data.refInFor;
    var _isString = typeof ref2 === "string" || typeof ref2 === "number";
    var _isRef = isRef(ref2);
    var refs = vm.$refs;
    if (_isString || _isRef) {
      if (isFor) {
        var existing = _isString ? refs[ref2] : ref2.value;
        if (isRemoval) {
          isArray(existing) && remove$2(existing, refValue);
        } else {
          if (!isArray(existing)) {
            if (_isString) {
              refs[ref2] = [refValue];
              setSetupRef(vm, ref2, refs[ref2]);
            } else {
              ref2.value = [refValue];
            }
          } else if (!existing.includes(refValue)) {
            existing.push(refValue);
          }
        }
      } else if (_isString) {
        if (isRemoval && refs[ref2] !== refValue) {
          return;
        }
        refs[ref2] = $refsValue;
        setSetupRef(vm, ref2, value);
      } else if (_isRef) {
        if (isRemoval && ref2.value !== refValue) {
          return;
        }
        ref2.value = value;
      } else ;
    }
  }
  function setSetupRef(_a, key, val) {
    var _setupState = _a._setupState;
    if (_setupState && hasOwn(_setupState, key)) {
      if (isRef(_setupState[key])) {
        _setupState[key].value = val;
      } else {
        _setupState[key] = val;
      }
    }
  }
  var emptyNode = new VNode("", {}, []);
  var hooks = ["create", "activate", "update", "remove", "destroy"];
  function sameVnode(a, b) {
    return a.key === b.key && a.asyncFactory === b.asyncFactory && (a.tag === b.tag && a.isComment === b.isComment && isDef(a.data) === isDef(b.data) && sameInputType(a, b) || isTrue(a.isAsyncPlaceholder) && isUndef(b.asyncFactory.error));
  }
  function sameInputType(a, b) {
    if (a.tag !== "input")
      return true;
    var i;
    var typeA = isDef(i = a.data) && isDef(i = i.attrs) && i.type;
    var typeB = isDef(i = b.data) && isDef(i = i.attrs) && i.type;
    return typeA === typeB || isTextInputType(typeA) && isTextInputType(typeB);
  }
  function createKeyToOldIdx(children, beginIdx, endIdx) {
    var i, key;
    var map = {};
    for (i = beginIdx; i <= endIdx; ++i) {
      key = children[i].key;
      if (isDef(key))
        map[key] = i;
    }
    return map;
  }
  function createPatchFunction(backend) {
    var i, j;
    var cbs = {};
    var modules2 = backend.modules, nodeOps2 = backend.nodeOps;
    for (i = 0; i < hooks.length; ++i) {
      cbs[hooks[i]] = [];
      for (j = 0; j < modules2.length; ++j) {
        if (isDef(modules2[j][hooks[i]])) {
          cbs[hooks[i]].push(modules2[j][hooks[i]]);
        }
      }
    }
    function emptyNodeAt(elm) {
      return new VNode(nodeOps2.tagName(elm).toLowerCase(), {}, [], void 0, elm);
    }
    function createRmCb(childElm, listeners) {
      function remove2() {
        if (--remove2.listeners === 0) {
          removeNode(childElm);
        }
      }
      remove2.listeners = listeners;
      return remove2;
    }
    function removeNode(el) {
      var parent = nodeOps2.parentNode(el);
      if (isDef(parent)) {
        nodeOps2.removeChild(parent, el);
      }
    }
    function createElm(vnode, insertedVnodeQueue, parentElm, refElm, nested, ownerArray, index2) {
      if (isDef(vnode.elm) && isDef(ownerArray)) {
        vnode = ownerArray[index2] = cloneVNode(vnode);
      }
      vnode.isRootInsert = !nested;
      if (createComponent2(vnode, insertedVnodeQueue, parentElm, refElm)) {
        return;
      }
      var data = vnode.data;
      var children = vnode.children;
      var tag = vnode.tag;
      if (isDef(tag)) {
        vnode.elm = vnode.ns ? nodeOps2.createElementNS(vnode.ns, tag) : nodeOps2.createElement(tag, vnode);
        setScope(vnode);
        createChildren(vnode, children, insertedVnodeQueue);
        if (isDef(data)) {
          invokeCreateHooks(vnode, insertedVnodeQueue);
        }
        insert(parentElm, vnode.elm, refElm);
      } else if (isTrue(vnode.isComment)) {
        vnode.elm = nodeOps2.createComment(vnode.text);
        insert(parentElm, vnode.elm, refElm);
      } else {
        vnode.elm = nodeOps2.createTextNode(vnode.text);
        insert(parentElm, vnode.elm, refElm);
      }
    }
    function createComponent2(vnode, insertedVnodeQueue, parentElm, refElm) {
      var i2 = vnode.data;
      if (isDef(i2)) {
        var isReactivated = isDef(vnode.componentInstance) && i2.keepAlive;
        if (isDef(i2 = i2.hook) && isDef(i2 = i2.init)) {
          i2(
            vnode,
            false
            /* hydrating */
          );
        }
        if (isDef(vnode.componentInstance)) {
          initComponent(vnode, insertedVnodeQueue);
          insert(parentElm, vnode.elm, refElm);
          if (isTrue(isReactivated)) {
            reactivateComponent(vnode, insertedVnodeQueue, parentElm, refElm);
          }
          return true;
        }
      }
    }
    function initComponent(vnode, insertedVnodeQueue) {
      if (isDef(vnode.data.pendingInsert)) {
        insertedVnodeQueue.push.apply(insertedVnodeQueue, vnode.data.pendingInsert);
        vnode.data.pendingInsert = null;
      }
      vnode.elm = vnode.componentInstance.$el;
      if (isPatchable(vnode)) {
        invokeCreateHooks(vnode, insertedVnodeQueue);
        setScope(vnode);
      } else {
        registerRef(vnode);
        insertedVnodeQueue.push(vnode);
      }
    }
    function reactivateComponent(vnode, insertedVnodeQueue, parentElm, refElm) {
      var i2;
      var innerNode = vnode;
      while (innerNode.componentInstance) {
        innerNode = innerNode.componentInstance._vnode;
        if (isDef(i2 = innerNode.data) && isDef(i2 = i2.transition)) {
          for (i2 = 0; i2 < cbs.activate.length; ++i2) {
            cbs.activate[i2](emptyNode, innerNode);
          }
          insertedVnodeQueue.push(innerNode);
          break;
        }
      }
      insert(parentElm, vnode.elm, refElm);
    }
    function insert(parent, elm, ref2) {
      if (isDef(parent)) {
        if (isDef(ref2)) {
          if (nodeOps2.parentNode(ref2) === parent) {
            nodeOps2.insertBefore(parent, elm, ref2);
          }
        } else {
          nodeOps2.appendChild(parent, elm);
        }
      }
    }
    function createChildren(vnode, children, insertedVnodeQueue) {
      if (isArray(children)) {
        for (var i_1 = 0; i_1 < children.length; ++i_1) {
          createElm(children[i_1], insertedVnodeQueue, vnode.elm, null, true, children, i_1);
        }
      } else if (isPrimitive(vnode.text)) {
        nodeOps2.appendChild(vnode.elm, nodeOps2.createTextNode(String(vnode.text)));
      }
    }
    function isPatchable(vnode) {
      while (vnode.componentInstance) {
        vnode = vnode.componentInstance._vnode;
      }
      return isDef(vnode.tag);
    }
    function invokeCreateHooks(vnode, insertedVnodeQueue) {
      for (var i_2 = 0; i_2 < cbs.create.length; ++i_2) {
        cbs.create[i_2](emptyNode, vnode);
      }
      i = vnode.data.hook;
      if (isDef(i)) {
        if (isDef(i.create))
          i.create(emptyNode, vnode);
        if (isDef(i.insert))
          insertedVnodeQueue.push(vnode);
      }
    }
    function setScope(vnode) {
      var i2;
      if (isDef(i2 = vnode.fnScopeId)) {
        nodeOps2.setStyleScope(vnode.elm, i2);
      } else {
        var ancestor = vnode;
        while (ancestor) {
          if (isDef(i2 = ancestor.context) && isDef(i2 = i2.$options._scopeId)) {
            nodeOps2.setStyleScope(vnode.elm, i2);
          }
          ancestor = ancestor.parent;
        }
      }
      if (isDef(i2 = activeInstance) && i2 !== vnode.context && i2 !== vnode.fnContext && isDef(i2 = i2.$options._scopeId)) {
        nodeOps2.setStyleScope(vnode.elm, i2);
      }
    }
    function addVnodes(parentElm, refElm, vnodes, startIdx, endIdx, insertedVnodeQueue) {
      for (; startIdx <= endIdx; ++startIdx) {
        createElm(vnodes[startIdx], insertedVnodeQueue, parentElm, refElm, false, vnodes, startIdx);
      }
    }
    function invokeDestroyHook(vnode) {
      var i2, j2;
      var data = vnode.data;
      if (isDef(data)) {
        if (isDef(i2 = data.hook) && isDef(i2 = i2.destroy))
          i2(vnode);
        for (i2 = 0; i2 < cbs.destroy.length; ++i2)
          cbs.destroy[i2](vnode);
      }
      if (isDef(i2 = vnode.children)) {
        for (j2 = 0; j2 < vnode.children.length; ++j2) {
          invokeDestroyHook(vnode.children[j2]);
        }
      }
    }
    function removeVnodes(vnodes, startIdx, endIdx) {
      for (; startIdx <= endIdx; ++startIdx) {
        var ch = vnodes[startIdx];
        if (isDef(ch)) {
          if (isDef(ch.tag)) {
            removeAndInvokeRemoveHook(ch);
            invokeDestroyHook(ch);
          } else {
            removeNode(ch.elm);
          }
        }
      }
    }
    function removeAndInvokeRemoveHook(vnode, rm) {
      if (isDef(rm) || isDef(vnode.data)) {
        var i_3;
        var listeners = cbs.remove.length + 1;
        if (isDef(rm)) {
          rm.listeners += listeners;
        } else {
          rm = createRmCb(vnode.elm, listeners);
        }
        if (isDef(i_3 = vnode.componentInstance) && isDef(i_3 = i_3._vnode) && isDef(i_3.data)) {
          removeAndInvokeRemoveHook(i_3, rm);
        }
        for (i_3 = 0; i_3 < cbs.remove.length; ++i_3) {
          cbs.remove[i_3](vnode, rm);
        }
        if (isDef(i_3 = vnode.data.hook) && isDef(i_3 = i_3.remove)) {
          i_3(vnode, rm);
        } else {
          rm();
        }
      } else {
        removeNode(vnode.elm);
      }
    }
    function updateChildren(parentElm, oldCh, newCh, insertedVnodeQueue, removeOnly) {
      var oldStartIdx = 0;
      var newStartIdx = 0;
      var oldEndIdx = oldCh.length - 1;
      var oldStartVnode = oldCh[0];
      var oldEndVnode = oldCh[oldEndIdx];
      var newEndIdx = newCh.length - 1;
      var newStartVnode = newCh[0];
      var newEndVnode = newCh[newEndIdx];
      var oldKeyToIdx, idxInOld, vnodeToMove, refElm;
      var canMove = !removeOnly;
      while (oldStartIdx <= oldEndIdx && newStartIdx <= newEndIdx) {
        if (isUndef(oldStartVnode)) {
          oldStartVnode = oldCh[++oldStartIdx];
        } else if (isUndef(oldEndVnode)) {
          oldEndVnode = oldCh[--oldEndIdx];
        } else if (sameVnode(oldStartVnode, newStartVnode)) {
          patchVnode(oldStartVnode, newStartVnode, insertedVnodeQueue, newCh, newStartIdx);
          oldStartVnode = oldCh[++oldStartIdx];
          newStartVnode = newCh[++newStartIdx];
        } else if (sameVnode(oldEndVnode, newEndVnode)) {
          patchVnode(oldEndVnode, newEndVnode, insertedVnodeQueue, newCh, newEndIdx);
          oldEndVnode = oldCh[--oldEndIdx];
          newEndVnode = newCh[--newEndIdx];
        } else if (sameVnode(oldStartVnode, newEndVnode)) {
          patchVnode(oldStartVnode, newEndVnode, insertedVnodeQueue, newCh, newEndIdx);
          canMove && nodeOps2.insertBefore(parentElm, oldStartVnode.elm, nodeOps2.nextSibling(oldEndVnode.elm));
          oldStartVnode = oldCh[++oldStartIdx];
          newEndVnode = newCh[--newEndIdx];
        } else if (sameVnode(oldEndVnode, newStartVnode)) {
          patchVnode(oldEndVnode, newStartVnode, insertedVnodeQueue, newCh, newStartIdx);
          canMove && nodeOps2.insertBefore(parentElm, oldEndVnode.elm, oldStartVnode.elm);
          oldEndVnode = oldCh[--oldEndIdx];
          newStartVnode = newCh[++newStartIdx];
        } else {
          if (isUndef(oldKeyToIdx))
            oldKeyToIdx = createKeyToOldIdx(oldCh, oldStartIdx, oldEndIdx);
          idxInOld = isDef(newStartVnode.key) ? oldKeyToIdx[newStartVnode.key] : findIdxInOld(newStartVnode, oldCh, oldStartIdx, oldEndIdx);
          if (isUndef(idxInOld)) {
            createElm(newStartVnode, insertedVnodeQueue, parentElm, oldStartVnode.elm, false, newCh, newStartIdx);
          } else {
            vnodeToMove = oldCh[idxInOld];
            if (sameVnode(vnodeToMove, newStartVnode)) {
              patchVnode(vnodeToMove, newStartVnode, insertedVnodeQueue, newCh, newStartIdx);
              oldCh[idxInOld] = void 0;
              canMove && nodeOps2.insertBefore(parentElm, vnodeToMove.elm, oldStartVnode.elm);
            } else {
              createElm(newStartVnode, insertedVnodeQueue, parentElm, oldStartVnode.elm, false, newCh, newStartIdx);
            }
          }
          newStartVnode = newCh[++newStartIdx];
        }
      }
      if (oldStartIdx > oldEndIdx) {
        refElm = isUndef(newCh[newEndIdx + 1]) ? null : newCh[newEndIdx + 1].elm;
        addVnodes(parentElm, refElm, newCh, newStartIdx, newEndIdx, insertedVnodeQueue);
      } else if (newStartIdx > newEndIdx) {
        removeVnodes(oldCh, oldStartIdx, oldEndIdx);
      }
    }
    function findIdxInOld(node, oldCh, start, end) {
      for (var i_5 = start; i_5 < end; i_5++) {
        var c = oldCh[i_5];
        if (isDef(c) && sameVnode(node, c))
          return i_5;
      }
    }
    function patchVnode(oldVnode, vnode, insertedVnodeQueue, ownerArray, index2, removeOnly) {
      if (oldVnode === vnode) {
        return;
      }
      if (isDef(vnode.elm) && isDef(ownerArray)) {
        vnode = ownerArray[index2] = cloneVNode(vnode);
      }
      var elm = vnode.elm = oldVnode.elm;
      if (isTrue(oldVnode.isAsyncPlaceholder)) {
        if (isDef(vnode.asyncFactory.resolved)) {
          hydrate(oldVnode.elm, vnode, insertedVnodeQueue);
        } else {
          vnode.isAsyncPlaceholder = true;
        }
        return;
      }
      if (isTrue(vnode.isStatic) && isTrue(oldVnode.isStatic) && vnode.key === oldVnode.key && (isTrue(vnode.isCloned) || isTrue(vnode.isOnce))) {
        vnode.componentInstance = oldVnode.componentInstance;
        return;
      }
      var i2;
      var data = vnode.data;
      if (isDef(data) && isDef(i2 = data.hook) && isDef(i2 = i2.prepatch)) {
        i2(oldVnode, vnode);
      }
      var oldCh = oldVnode.children;
      var ch = vnode.children;
      if (isDef(data) && isPatchable(vnode)) {
        for (i2 = 0; i2 < cbs.update.length; ++i2)
          cbs.update[i2](oldVnode, vnode);
        if (isDef(i2 = data.hook) && isDef(i2 = i2.update))
          i2(oldVnode, vnode);
      }
      if (isUndef(vnode.text)) {
        if (isDef(oldCh) && isDef(ch)) {
          if (oldCh !== ch)
            updateChildren(elm, oldCh, ch, insertedVnodeQueue, removeOnly);
        } else if (isDef(ch)) {
          if (isDef(oldVnode.text))
            nodeOps2.setTextContent(elm, "");
          addVnodes(elm, null, ch, 0, ch.length - 1, insertedVnodeQueue);
        } else if (isDef(oldCh)) {
          removeVnodes(oldCh, 0, oldCh.length - 1);
        } else if (isDef(oldVnode.text)) {
          nodeOps2.setTextContent(elm, "");
        }
      } else if (oldVnode.text !== vnode.text) {
        nodeOps2.setTextContent(elm, vnode.text);
      }
      if (isDef(data)) {
        if (isDef(i2 = data.hook) && isDef(i2 = i2.postpatch))
          i2(oldVnode, vnode);
      }
    }
    function invokeInsertHook(vnode, queue2, initial) {
      if (isTrue(initial) && isDef(vnode.parent)) {
        vnode.parent.data.pendingInsert = queue2;
      } else {
        for (var i_6 = 0; i_6 < queue2.length; ++i_6) {
          queue2[i_6].data.hook.insert(queue2[i_6]);
        }
      }
    }
    var isRenderedModule = makeMap("attrs,class,staticClass,staticStyle,key");
    function hydrate(elm, vnode, insertedVnodeQueue, inVPre) {
      var i2;
      var tag = vnode.tag, data = vnode.data, children = vnode.children;
      inVPre = inVPre || data && data.pre;
      vnode.elm = elm;
      if (isTrue(vnode.isComment) && isDef(vnode.asyncFactory)) {
        vnode.isAsyncPlaceholder = true;
        return true;
      }
      if (isDef(data)) {
        if (isDef(i2 = data.hook) && isDef(i2 = i2.init))
          i2(
            vnode,
            true
            /* hydrating */
          );
        if (isDef(i2 = vnode.componentInstance)) {
          initComponent(vnode, insertedVnodeQueue);
          return true;
        }
      }
      if (isDef(tag)) {
        if (isDef(children)) {
          if (!elm.hasChildNodes()) {
            createChildren(vnode, children, insertedVnodeQueue);
          } else {
            if (isDef(i2 = data) && isDef(i2 = i2.domProps) && isDef(i2 = i2.innerHTML)) {
              if (i2 !== elm.innerHTML) {
                return false;
              }
            } else {
              var childrenMatch = true;
              var childNode = elm.firstChild;
              for (var i_7 = 0; i_7 < children.length; i_7++) {
                if (!childNode || !hydrate(childNode, children[i_7], insertedVnodeQueue, inVPre)) {
                  childrenMatch = false;
                  break;
                }
                childNode = childNode.nextSibling;
              }
              if (!childrenMatch || childNode) {
                return false;
              }
            }
          }
        }
        if (isDef(data)) {
          var fullInvoke = false;
          for (var key in data) {
            if (!isRenderedModule(key)) {
              fullInvoke = true;
              invokeCreateHooks(vnode, insertedVnodeQueue);
              break;
            }
          }
          if (!fullInvoke && data["class"]) {
            traverse(data["class"]);
          }
        }
      } else if (elm.data !== vnode.text) {
        elm.data = vnode.text;
      }
      return true;
    }
    return function patch2(oldVnode, vnode, hydrating, removeOnly) {
      if (isUndef(vnode)) {
        if (isDef(oldVnode))
          invokeDestroyHook(oldVnode);
        return;
      }
      var isInitialPatch = false;
      var insertedVnodeQueue = [];
      if (isUndef(oldVnode)) {
        isInitialPatch = true;
        createElm(vnode, insertedVnodeQueue);
      } else {
        var isRealElement = isDef(oldVnode.nodeType);
        if (!isRealElement && sameVnode(oldVnode, vnode)) {
          patchVnode(oldVnode, vnode, insertedVnodeQueue, null, null, removeOnly);
        } else {
          if (isRealElement) {
            if (oldVnode.nodeType === 1 && oldVnode.hasAttribute(SSR_ATTR)) {
              oldVnode.removeAttribute(SSR_ATTR);
              hydrating = true;
            }
            if (isTrue(hydrating)) {
              if (hydrate(oldVnode, vnode, insertedVnodeQueue)) {
                invokeInsertHook(vnode, insertedVnodeQueue, true);
                return oldVnode;
              }
            }
            oldVnode = emptyNodeAt(oldVnode);
          }
          var oldElm = oldVnode.elm;
          var parentElm = nodeOps2.parentNode(oldElm);
          createElm(
            vnode,
            insertedVnodeQueue,
            // extremely rare edge case: do not insert if old element is in a
            // leaving transition. Only happens when combining transition +
            // keep-alive + HOCs. (#4590)
            oldElm._leaveCb ? null : parentElm,
            nodeOps2.nextSibling(oldElm)
          );
          if (isDef(vnode.parent)) {
            var ancestor = vnode.parent;
            var patchable = isPatchable(vnode);
            while (ancestor) {
              for (var i_8 = 0; i_8 < cbs.destroy.length; ++i_8) {
                cbs.destroy[i_8](ancestor);
              }
              ancestor.elm = vnode.elm;
              if (patchable) {
                for (var i_9 = 0; i_9 < cbs.create.length; ++i_9) {
                  cbs.create[i_9](emptyNode, ancestor);
                }
                var insert_1 = ancestor.data.hook.insert;
                if (insert_1.merged) {
                  var cloned = insert_1.fns.slice(1);
                  for (var i_10 = 0; i_10 < cloned.length; i_10++) {
                    cloned[i_10]();
                  }
                }
              } else {
                registerRef(ancestor);
              }
              ancestor = ancestor.parent;
            }
          }
          if (isDef(parentElm)) {
            removeVnodes([oldVnode], 0, 0);
          } else if (isDef(oldVnode.tag)) {
            invokeDestroyHook(oldVnode);
          }
        }
      }
      invokeInsertHook(vnode, insertedVnodeQueue, isInitialPatch);
      return vnode.elm;
    };
  }
  var directives = {
    create: updateDirectives,
    update: updateDirectives,
    destroy: function unbindDirectives(vnode) {
      updateDirectives(vnode, emptyNode);
    }
  };
  function updateDirectives(oldVnode, vnode) {
    if (oldVnode.data.directives || vnode.data.directives) {
      _update(oldVnode, vnode);
    }
  }
  function _update(oldVnode, vnode) {
    var isCreate = oldVnode === emptyNode;
    var isDestroy = vnode === emptyNode;
    var oldDirs = normalizeDirectives(oldVnode.data.directives, oldVnode.context);
    var newDirs = normalizeDirectives(vnode.data.directives, vnode.context);
    var dirsWithInsert = [];
    var dirsWithPostpatch = [];
    var key, oldDir, dir;
    for (key in newDirs) {
      oldDir = oldDirs[key];
      dir = newDirs[key];
      if (!oldDir) {
        callHook(dir, "bind", vnode, oldVnode);
        if (dir.def && dir.def.inserted) {
          dirsWithInsert.push(dir);
        }
      } else {
        dir.oldValue = oldDir.value;
        dir.oldArg = oldDir.arg;
        callHook(dir, "update", vnode, oldVnode);
        if (dir.def && dir.def.componentUpdated) {
          dirsWithPostpatch.push(dir);
        }
      }
    }
    if (dirsWithInsert.length) {
      var callInsert = function() {
        for (var i = 0; i < dirsWithInsert.length; i++) {
          callHook(dirsWithInsert[i], "inserted", vnode, oldVnode);
        }
      };
      if (isCreate) {
        mergeVNodeHook(vnode, "insert", callInsert);
      } else {
        callInsert();
      }
    }
    if (dirsWithPostpatch.length) {
      mergeVNodeHook(vnode, "postpatch", function() {
        for (var i = 0; i < dirsWithPostpatch.length; i++) {
          callHook(dirsWithPostpatch[i], "componentUpdated", vnode, oldVnode);
        }
      });
    }
    if (!isCreate) {
      for (key in oldDirs) {
        if (!newDirs[key]) {
          callHook(oldDirs[key], "unbind", oldVnode, oldVnode, isDestroy);
        }
      }
    }
  }
  var emptyModifiers = /* @__PURE__ */ Object.create(null);
  function normalizeDirectives(dirs, vm) {
    var res = /* @__PURE__ */ Object.create(null);
    if (!dirs) {
      return res;
    }
    var i, dir;
    for (i = 0; i < dirs.length; i++) {
      dir = dirs[i];
      if (!dir.modifiers) {
        dir.modifiers = emptyModifiers;
      }
      res[getRawDirName(dir)] = dir;
      if (vm._setupState && vm._setupState.__sfc) {
        var setupDef = dir.def || resolveAsset(vm, "_setupState", "v-" + dir.name);
        if (typeof setupDef === "function") {
          dir.def = {
            bind: setupDef,
            update: setupDef
          };
        } else {
          dir.def = setupDef;
        }
      }
      dir.def = dir.def || resolveAsset(vm.$options, "directives", dir.name);
    }
    return res;
  }
  function getRawDirName(dir) {
    return dir.rawName || "".concat(dir.name, ".").concat(Object.keys(dir.modifiers || {}).join("."));
  }
  function callHook(dir, hook, vnode, oldVnode, isDestroy) {
    var fn = dir.def && dir.def[hook];
    if (fn) {
      try {
        fn(vnode.elm, dir, vnode, oldVnode, isDestroy);
      } catch (e) {
        handleError(e, vnode.context, "directive ".concat(dir.name, " ").concat(hook, " hook"));
      }
    }
  }
  var baseModules = [ref, directives];
  function updateAttrs(oldVnode, vnode) {
    var opts2 = vnode.componentOptions;
    if (isDef(opts2) && opts2.Ctor.options.inheritAttrs === false) {
      return;
    }
    if (isUndef(oldVnode.data.attrs) && isUndef(vnode.data.attrs)) {
      return;
    }
    var key, cur, old;
    var elm = vnode.elm;
    var oldAttrs = oldVnode.data.attrs || {};
    var attrs2 = vnode.data.attrs || {};
    if (isDef(attrs2.__ob__) || isTrue(attrs2._v_attr_proxy)) {
      attrs2 = vnode.data.attrs = extend({}, attrs2);
    }
    for (key in attrs2) {
      cur = attrs2[key];
      old = oldAttrs[key];
      if (old !== cur) {
        setAttr(elm, key, cur, vnode.data.pre);
      }
    }
    if ((isIE || isEdge) && attrs2.value !== oldAttrs.value) {
      setAttr(elm, "value", attrs2.value);
    }
    for (key in oldAttrs) {
      if (isUndef(attrs2[key])) {
        if (isXlink(key)) {
          elm.removeAttributeNS(xlinkNS, getXlinkProp(key));
        } else if (!isEnumeratedAttr(key)) {
          elm.removeAttribute(key);
        }
      }
    }
  }
  function setAttr(el, key, value, isInPre) {
    if (isInPre || el.tagName.indexOf("-") > -1) {
      baseSetAttr(el, key, value);
    } else if (isBooleanAttr(key)) {
      if (isFalsyAttrValue(value)) {
        el.removeAttribute(key);
      } else {
        value = key === "allowfullscreen" && el.tagName === "EMBED" ? "true" : key;
        el.setAttribute(key, value);
      }
    } else if (isEnumeratedAttr(key)) {
      el.setAttribute(key, convertEnumeratedValue(key, value));
    } else if (isXlink(key)) {
      if (isFalsyAttrValue(value)) {
        el.removeAttributeNS(xlinkNS, getXlinkProp(key));
      } else {
        el.setAttributeNS(xlinkNS, key, value);
      }
    } else {
      baseSetAttr(el, key, value);
    }
  }
  function baseSetAttr(el, key, value) {
    if (isFalsyAttrValue(value)) {
      el.removeAttribute(key);
    } else {
      if (isIE && !isIE9 && el.tagName === "TEXTAREA" && key === "placeholder" && value !== "" && !el.__ieph) {
        var blocker_1 = function(e) {
          e.stopImmediatePropagation();
          el.removeEventListener("input", blocker_1);
        };
        el.addEventListener("input", blocker_1);
        el.__ieph = true;
      }
      el.setAttribute(key, value);
    }
  }
  var attrs = {
    create: updateAttrs,
    update: updateAttrs
  };
  function updateClass(oldVnode, vnode) {
    var el = vnode.elm;
    var data = vnode.data;
    var oldData = oldVnode.data;
    if (isUndef(data.staticClass) && isUndef(data.class) && (isUndef(oldData) || isUndef(oldData.staticClass) && isUndef(oldData.class))) {
      return;
    }
    var cls = genClassForVnode(vnode);
    var transitionClass = el._transitionClasses;
    if (isDef(transitionClass)) {
      cls = concat(cls, stringifyClass(transitionClass));
    }
    if (cls !== el._prevClass) {
      el.setAttribute("class", cls);
      el._prevClass = cls;
    }
  }
  var klass = {
    create: updateClass,
    update: updateClass
  };
  var RANGE_TOKEN = "__r";
  var CHECKBOX_RADIO_TOKEN = "__c";
  function normalizeEvents(on) {
    if (isDef(on[RANGE_TOKEN])) {
      var event_1 = isIE ? "change" : "input";
      on[event_1] = [].concat(on[RANGE_TOKEN], on[event_1] || []);
      delete on[RANGE_TOKEN];
    }
    if (isDef(on[CHECKBOX_RADIO_TOKEN])) {
      on.change = [].concat(on[CHECKBOX_RADIO_TOKEN], on.change || []);
      delete on[CHECKBOX_RADIO_TOKEN];
    }
  }
  var target;
  function createOnceHandler(event, handler, capture) {
    var _target = target;
    return function onceHandler() {
      var res = handler.apply(null, arguments);
      if (res !== null) {
        remove(event, onceHandler, capture, _target);
      }
    };
  }
  var useMicrotaskFix = isUsingMicroTask && !(isFF && Number(isFF[1]) <= 53);
  function add(name, handler, capture, passive) {
    if (useMicrotaskFix) {
      var attachedTimestamp_1 = currentFlushTimestamp;
      var original_1 = handler;
      handler = original_1._wrapper = function(e) {
        if (
          // no bubbling, should always fire.
          // this is just a safety net in case event.timeStamp is unreliable in
          // certain weird environments...
          e.target === e.currentTarget || // event is fired after handler attachment
          e.timeStamp >= attachedTimestamp_1 || // bail for environments that have buggy event.timeStamp implementations
          // #9462 iOS 9 bug: event.timeStamp is 0 after history.pushState
          // #9681 QtWebEngine event.timeStamp is negative value
          e.timeStamp <= 0 || // #9448 bail if event is fired in another document in a multi-page
          // electron/nw.js app, since event.timeStamp will be using a different
          // starting reference
          e.target.ownerDocument !== document
        ) {
          return original_1.apply(this, arguments);
        }
      };
    }
    target.addEventListener(name, handler, supportsPassive ? { capture, passive } : capture);
  }
  function remove(name, handler, capture, _target) {
    (_target || target).removeEventListener(
      name,
      //@ts-expect-error
      handler._wrapper || handler,
      capture
    );
  }
  function updateDOMListeners(oldVnode, vnode) {
    if (isUndef(oldVnode.data.on) && isUndef(vnode.data.on)) {
      return;
    }
    var on = vnode.data.on || {};
    var oldOn = oldVnode.data.on || {};
    target = vnode.elm || oldVnode.elm;
    normalizeEvents(on);
    updateListeners(on, oldOn, add, remove, createOnceHandler, vnode.context);
    target = void 0;
  }
  var events = {
    create: updateDOMListeners,
    update: updateDOMListeners,
    // @ts-expect-error emptyNode has actually data
    destroy: function(vnode) {
      return updateDOMListeners(vnode, emptyNode);
    }
  };
  var svgContainer;
  function updateDOMProps(oldVnode, vnode) {
    if (isUndef(oldVnode.data.domProps) && isUndef(vnode.data.domProps)) {
      return;
    }
    var key, cur;
    var elm = vnode.elm;
    var oldProps = oldVnode.data.domProps || {};
    var props2 = vnode.data.domProps || {};
    if (isDef(props2.__ob__) || isTrue(props2._v_attr_proxy)) {
      props2 = vnode.data.domProps = extend({}, props2);
    }
    for (key in oldProps) {
      if (!(key in props2)) {
        elm[key] = "";
      }
    }
    for (key in props2) {
      cur = props2[key];
      if (key === "textContent" || key === "innerHTML") {
        if (vnode.children)
          vnode.children.length = 0;
        if (cur === oldProps[key])
          continue;
        if (elm.childNodes.length === 1) {
          elm.removeChild(elm.childNodes[0]);
        }
      }
      if (key === "value" && elm.tagName !== "PROGRESS") {
        elm._value = cur;
        var strCur = isUndef(cur) ? "" : String(cur);
        if (shouldUpdateValue(elm, strCur)) {
          elm.value = strCur;
        }
      } else if (key === "innerHTML" && isSVG(elm.tagName) && isUndef(elm.innerHTML)) {
        svgContainer = svgContainer || document.createElement("div");
        svgContainer.innerHTML = "<svg>".concat(cur, "</svg>");
        var svg = svgContainer.firstChild;
        while (elm.firstChild) {
          elm.removeChild(elm.firstChild);
        }
        while (svg.firstChild) {
          elm.appendChild(svg.firstChild);
        }
      } else if (
        // skip the update if old and new VDOM state is the same.
        // `value` is handled separately because the DOM value may be temporarily
        // out of sync with VDOM state due to focus, composition and modifiers.
        // This  #4521 by skipping the unnecessary `checked` update.
        cur !== oldProps[key]
      ) {
        try {
          elm[key] = cur;
        } catch (e) {
        }
      }
    }
  }
  function shouldUpdateValue(elm, checkVal) {
    return (
      //@ts-expect-error
      !elm.composing && (elm.tagName === "OPTION" || isNotInFocusAndDirty(elm, checkVal) || isDirtyWithModifiers(elm, checkVal))
    );
  }
  function isNotInFocusAndDirty(elm, checkVal) {
    var notInFocus = true;
    try {
      notInFocus = document.activeElement !== elm;
    } catch (e) {
    }
    return notInFocus && elm.value !== checkVal;
  }
  function isDirtyWithModifiers(elm, newVal) {
    var value = elm.value;
    var modifiers = elm._vModifiers;
    if (isDef(modifiers)) {
      if (modifiers.number) {
        return toNumber(value) !== toNumber(newVal);
      }
      if (modifiers.trim) {
        return value.trim() !== newVal.trim();
      }
    }
    return value !== newVal;
  }
  var domProps = {
    create: updateDOMProps,
    update: updateDOMProps
  };
  var parseStyleText = cached(function(cssText) {
    var res = {};
    var listDelimiter = /;(?![^(]*\))/g;
    var propertyDelimiter = /:(.+)/;
    cssText.split(listDelimiter).forEach(function(item) {
      if (item) {
        var tmp = item.split(propertyDelimiter);
        tmp.length > 1 && (res[tmp[0].trim()] = tmp[1].trim());
      }
    });
    return res;
  });
  function normalizeStyleData(data) {
    var style2 = normalizeStyleBinding(data.style);
    return data.staticStyle ? extend(data.staticStyle, style2) : style2;
  }
  function normalizeStyleBinding(bindingStyle) {
    if (Array.isArray(bindingStyle)) {
      return toObject(bindingStyle);
    }
    if (typeof bindingStyle === "string") {
      return parseStyleText(bindingStyle);
    }
    return bindingStyle;
  }
  function getStyle(vnode, checkChild) {
    var res = {};
    var styleData;
    {
      var childNode = vnode;
      while (childNode.componentInstance) {
        childNode = childNode.componentInstance._vnode;
        if (childNode && childNode.data && (styleData = normalizeStyleData(childNode.data))) {
          extend(res, styleData);
        }
      }
    }
    if (styleData = normalizeStyleData(vnode.data)) {
      extend(res, styleData);
    }
    var parentNode2 = vnode;
    while (parentNode2 = parentNode2.parent) {
      if (parentNode2.data && (styleData = normalizeStyleData(parentNode2.data))) {
        extend(res, styleData);
      }
    }
    return res;
  }
  var cssVarRE = /^--/;
  var importantRE = /\s*!important$/;
  var setProp = function(el, name, val) {
    if (cssVarRE.test(name)) {
      el.style.setProperty(name, val);
    } else if (importantRE.test(val)) {
      el.style.setProperty(hyphenate(name), val.replace(importantRE, ""), "important");
    } else {
      var normalizedName = normalize(name);
      if (Array.isArray(val)) {
        for (var i = 0, len = val.length; i < len; i++) {
          el.style[normalizedName] = val[i];
        }
      } else {
        el.style[normalizedName] = val;
      }
    }
  };
  var vendorNames = ["Webkit", "Moz", "ms"];
  var emptyStyle;
  var normalize = cached(function(prop) {
    emptyStyle = emptyStyle || document.createElement("div").style;
    prop = camelize(prop);
    if (prop !== "filter" && prop in emptyStyle) {
      return prop;
    }
    var capName = prop.charAt(0).toUpperCase() + prop.slice(1);
    for (var i = 0; i < vendorNames.length; i++) {
      var name_1 = vendorNames[i] + capName;
      if (name_1 in emptyStyle) {
        return name_1;
      }
    }
  });
  function updateStyle(oldVnode, vnode) {
    var data = vnode.data;
    var oldData = oldVnode.data;
    if (isUndef(data.staticStyle) && isUndef(data.style) && isUndef(oldData.staticStyle) && isUndef(oldData.style)) {
      return;
    }
    var cur, name;
    var el = vnode.elm;
    var oldStaticStyle = oldData.staticStyle;
    var oldStyleBinding = oldData.normalizedStyle || oldData.style || {};
    var oldStyle = oldStaticStyle || oldStyleBinding;
    var style2 = normalizeStyleBinding(vnode.data.style) || {};
    vnode.data.normalizedStyle = isDef(style2.__ob__) ? extend({}, style2) : style2;
    var newStyle = getStyle(vnode);
    for (name in oldStyle) {
      if (isUndef(newStyle[name])) {
        setProp(el, name, "");
      }
    }
    for (name in newStyle) {
      cur = newStyle[name];
      setProp(el, name, cur == null ? "" : cur);
    }
  }
  var style = {
    create: updateStyle,
    update: updateStyle
  };
  var whitespaceRE = /\s+/;
  function addClass(el, cls) {
    if (!cls || !(cls = cls.trim())) {
      return;
    }
    if (el.classList) {
      if (cls.indexOf(" ") > -1) {
        cls.split(whitespaceRE).forEach(function(c) {
          return el.classList.add(c);
        });
      } else {
        el.classList.add(cls);
      }
    } else {
      var cur = " ".concat(el.getAttribute("class") || "", " ");
      if (cur.indexOf(" " + cls + " ") < 0) {
        el.setAttribute("class", (cur + cls).trim());
      }
    }
  }
  function removeClass(el, cls) {
    if (!cls || !(cls = cls.trim())) {
      return;
    }
    if (el.classList) {
      if (cls.indexOf(" ") > -1) {
        cls.split(whitespaceRE).forEach(function(c) {
          return el.classList.remove(c);
        });
      } else {
        el.classList.remove(cls);
      }
      if (!el.classList.length) {
        el.removeAttribute("class");
      }
    } else {
      var cur = " ".concat(el.getAttribute("class") || "", " ");
      var tar = " " + cls + " ";
      while (cur.indexOf(tar) >= 0) {
        cur = cur.replace(tar, " ");
      }
      cur = cur.trim();
      if (cur) {
        el.setAttribute("class", cur);
      } else {
        el.removeAttribute("class");
      }
    }
  }
  function resolveTransition(def2) {
    if (!def2) {
      return;
    }
    if (typeof def2 === "object") {
      var res = {};
      if (def2.css !== false) {
        extend(res, autoCssTransition(def2.name || "v"));
      }
      extend(res, def2);
      return res;
    } else if (typeof def2 === "string") {
      return autoCssTransition(def2);
    }
  }
  var autoCssTransition = cached(function(name) {
    return {
      enterClass: "".concat(name, "-enter"),
      enterToClass: "".concat(name, "-enter-to"),
      enterActiveClass: "".concat(name, "-enter-active"),
      leaveClass: "".concat(name, "-leave"),
      leaveToClass: "".concat(name, "-leave-to"),
      leaveActiveClass: "".concat(name, "-leave-active")
    };
  });
  var hasTransition = inBrowser && !isIE9;
  var TRANSITION = "transition";
  var ANIMATION = "animation";
  var transitionProp = "transition";
  var transitionEndEvent = "transitionend";
  var animationProp = "animation";
  var animationEndEvent = "animationend";
  if (hasTransition) {
    if (window.ontransitionend === void 0 && window.onwebkittransitionend !== void 0) {
      transitionProp = "WebkitTransition";
      transitionEndEvent = "webkitTransitionEnd";
    }
    if (window.onanimationend === void 0 && window.onwebkitanimationend !== void 0) {
      animationProp = "WebkitAnimation";
      animationEndEvent = "webkitAnimationEnd";
    }
  }
  var raf = inBrowser ? window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : setTimeout : (
    /* istanbul ignore next */
    function(fn) {
      return fn();
    }
  );
  function nextFrame(fn) {
    raf(function() {
      raf(fn);
    });
  }
  function addTransitionClass(el, cls) {
    var transitionClasses = el._transitionClasses || (el._transitionClasses = []);
    if (transitionClasses.indexOf(cls) < 0) {
      transitionClasses.push(cls);
      addClass(el, cls);
    }
  }
  function removeTransitionClass(el, cls) {
    if (el._transitionClasses) {
      remove$2(el._transitionClasses, cls);
    }
    removeClass(el, cls);
  }
  function whenTransitionEnds(el, expectedType, cb) {
    var _a = getTransitionInfo(el, expectedType), type = _a.type, timeout = _a.timeout, propCount = _a.propCount;
    if (!type)
      return cb();
    var event = type === TRANSITION ? transitionEndEvent : animationEndEvent;
    var ended = 0;
    var end = function() {
      el.removeEventListener(event, onEnd);
      cb();
    };
    var onEnd = function(e) {
      if (e.target === el) {
        if (++ended >= propCount) {
          end();
        }
      }
    };
    setTimeout(function() {
      if (ended < propCount) {
        end();
      }
    }, timeout + 1);
    el.addEventListener(event, onEnd);
  }
  var transformRE = /\b(transform|all)(,|$)/;
  function getTransitionInfo(el, expectedType) {
    var styles = window.getComputedStyle(el);
    var transitionDelays = (styles[transitionProp + "Delay"] || "").split(", ");
    var transitionDurations = (styles[transitionProp + "Duration"] || "").split(", ");
    var transitionTimeout = getTimeout(transitionDelays, transitionDurations);
    var animationDelays = (styles[animationProp + "Delay"] || "").split(", ");
    var animationDurations = (styles[animationProp + "Duration"] || "").split(", ");
    var animationTimeout = getTimeout(animationDelays, animationDurations);
    var type;
    var timeout = 0;
    var propCount = 0;
    if (expectedType === TRANSITION) {
      if (transitionTimeout > 0) {
        type = TRANSITION;
        timeout = transitionTimeout;
        propCount = transitionDurations.length;
      }
    } else if (expectedType === ANIMATION) {
      if (animationTimeout > 0) {
        type = ANIMATION;
        timeout = animationTimeout;
        propCount = animationDurations.length;
      }
    } else {
      timeout = Math.max(transitionTimeout, animationTimeout);
      type = timeout > 0 ? transitionTimeout > animationTimeout ? TRANSITION : ANIMATION : null;
      propCount = type ? type === TRANSITION ? transitionDurations.length : animationDurations.length : 0;
    }
    var hasTransform = type === TRANSITION && transformRE.test(styles[transitionProp + "Property"]);
    return {
      type,
      timeout,
      propCount,
      hasTransform
    };
  }
  function getTimeout(delays, durations) {
    while (delays.length < durations.length) {
      delays = delays.concat(delays);
    }
    return Math.max.apply(null, durations.map(function(d, i) {
      return toMs(d) + toMs(delays[i]);
    }));
  }
  function toMs(s) {
    return Number(s.slice(0, -1).replace(",", ".")) * 1e3;
  }
  function enter(vnode, toggleDisplay) {
    var el = vnode.elm;
    if (isDef(el._leaveCb)) {
      el._leaveCb.cancelled = true;
      el._leaveCb();
    }
    var data = resolveTransition(vnode.data.transition);
    if (isUndef(data)) {
      return;
    }
    if (isDef(el._enterCb) || el.nodeType !== 1) {
      return;
    }
    var css = data.css, type = data.type, enterClass = data.enterClass, enterToClass = data.enterToClass, enterActiveClass = data.enterActiveClass, appearClass = data.appearClass, appearToClass = data.appearToClass, appearActiveClass = data.appearActiveClass, beforeEnter = data.beforeEnter, enter2 = data.enter, afterEnter = data.afterEnter, enterCancelled = data.enterCancelled, beforeAppear = data.beforeAppear, appear = data.appear, afterAppear = data.afterAppear, appearCancelled = data.appearCancelled, duration = data.duration;
    var context = activeInstance;
    var transitionNode = activeInstance.$vnode;
    while (transitionNode && transitionNode.parent) {
      context = transitionNode.context;
      transitionNode = transitionNode.parent;
    }
    var isAppear = !context._isMounted || !vnode.isRootInsert;
    if (isAppear && !appear && appear !== "") {
      return;
    }
    var startClass = isAppear && appearClass ? appearClass : enterClass;
    var activeClass = isAppear && appearActiveClass ? appearActiveClass : enterActiveClass;
    var toClass = isAppear && appearToClass ? appearToClass : enterToClass;
    var beforeEnterHook = isAppear ? beforeAppear || beforeEnter : beforeEnter;
    var enterHook = isAppear ? isFunction(appear) ? appear : enter2 : enter2;
    var afterEnterHook = isAppear ? afterAppear || afterEnter : afterEnter;
    var enterCancelledHook = isAppear ? appearCancelled || enterCancelled : enterCancelled;
    var explicitEnterDuration = toNumber(isObject(duration) ? duration.enter : duration);
    var expectsCSS = css !== false && !isIE9;
    var userWantsControl = getHookArgumentsLength(enterHook);
    var cb = el._enterCb = once(function() {
      if (expectsCSS) {
        removeTransitionClass(el, toClass);
        removeTransitionClass(el, activeClass);
      }
      if (cb.cancelled) {
        if (expectsCSS) {
          removeTransitionClass(el, startClass);
        }
        enterCancelledHook && enterCancelledHook(el);
      } else {
        afterEnterHook && afterEnterHook(el);
      }
      el._enterCb = null;
    });
    if (!vnode.data.show) {
      mergeVNodeHook(vnode, "insert", function() {
        var parent = el.parentNode;
        var pendingNode = parent && parent._pending && parent._pending[vnode.key];
        if (pendingNode && pendingNode.tag === vnode.tag && pendingNode.elm._leaveCb) {
          pendingNode.elm._leaveCb();
        }
        enterHook && enterHook(el, cb);
      });
    }
    beforeEnterHook && beforeEnterHook(el);
    if (expectsCSS) {
      addTransitionClass(el, startClass);
      addTransitionClass(el, activeClass);
      nextFrame(function() {
        removeTransitionClass(el, startClass);
        if (!cb.cancelled) {
          addTransitionClass(el, toClass);
          if (!userWantsControl) {
            if (isValidDuration(explicitEnterDuration)) {
              setTimeout(cb, explicitEnterDuration);
            } else {
              whenTransitionEnds(el, type, cb);
            }
          }
        }
      });
    }
    if (vnode.data.show) {
      toggleDisplay && toggleDisplay();
      enterHook && enterHook(el, cb);
    }
    if (!expectsCSS && !userWantsControl) {
      cb();
    }
  }
  function leave(vnode, rm) {
    var el = vnode.elm;
    if (isDef(el._enterCb)) {
      el._enterCb.cancelled = true;
      el._enterCb();
    }
    var data = resolveTransition(vnode.data.transition);
    if (isUndef(data) || el.nodeType !== 1) {
      return rm();
    }
    if (isDef(el._leaveCb)) {
      return;
    }
    var css = data.css, type = data.type, leaveClass = data.leaveClass, leaveToClass = data.leaveToClass, leaveActiveClass = data.leaveActiveClass, beforeLeave = data.beforeLeave, leave2 = data.leave, afterLeave = data.afterLeave, leaveCancelled = data.leaveCancelled, delayLeave = data.delayLeave, duration = data.duration;
    var expectsCSS = css !== false && !isIE9;
    var userWantsControl = getHookArgumentsLength(leave2);
    var explicitLeaveDuration = toNumber(isObject(duration) ? duration.leave : duration);
    var cb = el._leaveCb = once(function() {
      if (el.parentNode && el.parentNode._pending) {
        el.parentNode._pending[vnode.key] = null;
      }
      if (expectsCSS) {
        removeTransitionClass(el, leaveToClass);
        removeTransitionClass(el, leaveActiveClass);
      }
      if (cb.cancelled) {
        if (expectsCSS) {
          removeTransitionClass(el, leaveClass);
        }
        leaveCancelled && leaveCancelled(el);
      } else {
        rm();
        afterLeave && afterLeave(el);
      }
      el._leaveCb = null;
    });
    if (delayLeave) {
      delayLeave(performLeave);
    } else {
      performLeave();
    }
    function performLeave() {
      if (cb.cancelled) {
        return;
      }
      if (!vnode.data.show && el.parentNode) {
        (el.parentNode._pending || (el.parentNode._pending = {}))[vnode.key] = vnode;
      }
      beforeLeave && beforeLeave(el);
      if (expectsCSS) {
        addTransitionClass(el, leaveClass);
        addTransitionClass(el, leaveActiveClass);
        nextFrame(function() {
          removeTransitionClass(el, leaveClass);
          if (!cb.cancelled) {
            addTransitionClass(el, leaveToClass);
            if (!userWantsControl) {
              if (isValidDuration(explicitLeaveDuration)) {
                setTimeout(cb, explicitLeaveDuration);
              } else {
                whenTransitionEnds(el, type, cb);
              }
            }
          }
        });
      }
      leave2 && leave2(el, cb);
      if (!expectsCSS && !userWantsControl) {
        cb();
      }
    }
  }
  function isValidDuration(val) {
    return typeof val === "number" && !isNaN(val);
  }
  function getHookArgumentsLength(fn) {
    if (isUndef(fn)) {
      return false;
    }
    var invokerFns = fn.fns;
    if (isDef(invokerFns)) {
      return getHookArgumentsLength(Array.isArray(invokerFns) ? invokerFns[0] : invokerFns);
    } else {
      return (fn._length || fn.length) > 1;
    }
  }
  function _enter(_, vnode) {
    if (vnode.data.show !== true) {
      enter(vnode);
    }
  }
  var transition = inBrowser ? {
    create: _enter,
    activate: _enter,
    remove: function(vnode, rm) {
      if (vnode.data.show !== true) {
        leave(vnode, rm);
      } else {
        rm();
      }
    }
  } : {};
  var platformModules = [attrs, klass, events, domProps, style, transition];
  var modules = platformModules.concat(baseModules);
  var patch = createPatchFunction({ nodeOps, modules });
  if (isIE9) {
    document.addEventListener("selectionchange", function() {
      var el = document.activeElement;
      if (el && el.vmodel) {
        trigger(el, "input");
      }
    });
  }
  var directive = {
    inserted: function(el, binding, vnode, oldVnode) {
      if (vnode.tag === "select") {
        if (oldVnode.elm && !oldVnode.elm._vOptions) {
          mergeVNodeHook(vnode, "postpatch", function() {
            directive.componentUpdated(el, binding, vnode);
          });
        } else {
          setSelected(el, binding, vnode.context);
        }
        el._vOptions = [].map.call(el.options, getValue);
      } else if (vnode.tag === "textarea" || isTextInputType(el.type)) {
        el._vModifiers = binding.modifiers;
        if (!binding.modifiers.lazy) {
          el.addEventListener("compositionstart", onCompositionStart);
          el.addEventListener("compositionend", onCompositionEnd);
          el.addEventListener("change", onCompositionEnd);
          if (isIE9) {
            el.vmodel = true;
          }
        }
      }
    },
    componentUpdated: function(el, binding, vnode) {
      if (vnode.tag === "select") {
        setSelected(el, binding, vnode.context);
        var prevOptions_1 = el._vOptions;
        var curOptions_1 = el._vOptions = [].map.call(el.options, getValue);
        if (curOptions_1.some(function(o, i) {
          return !looseEqual(o, prevOptions_1[i]);
        })) {
          var needReset = el.multiple ? binding.value.some(function(v2) {
            return hasNoMatchingOption(v2, curOptions_1);
          }) : binding.value !== binding.oldValue && hasNoMatchingOption(binding.value, curOptions_1);
          if (needReset) {
            trigger(el, "change");
          }
        }
      }
    }
  };
  function setSelected(el, binding, vm) {
    actuallySetSelected(el, binding);
    if (isIE || isEdge) {
      setTimeout(function() {
        actuallySetSelected(el, binding);
      }, 0);
    }
  }
  function actuallySetSelected(el, binding, vm) {
    var value = binding.value;
    var isMultiple = el.multiple;
    if (isMultiple && !Array.isArray(value)) {
      return;
    }
    var selected, option;
    for (var i = 0, l = el.options.length; i < l; i++) {
      option = el.options[i];
      if (isMultiple) {
        selected = looseIndexOf(value, getValue(option)) > -1;
        if (option.selected !== selected) {
          option.selected = selected;
        }
      } else {
        if (looseEqual(getValue(option), value)) {
          if (el.selectedIndex !== i) {
            el.selectedIndex = i;
          }
          return;
        }
      }
    }
    if (!isMultiple) {
      el.selectedIndex = -1;
    }
  }
  function hasNoMatchingOption(value, options) {
    return options.every(function(o) {
      return !looseEqual(o, value);
    });
  }
  function getValue(option) {
    return "_value" in option ? option._value : option.value;
  }
  function onCompositionStart(e) {
    e.target.composing = true;
  }
  function onCompositionEnd(e) {
    if (!e.target.composing)
      return;
    e.target.composing = false;
    trigger(e.target, "input");
  }
  function trigger(el, type) {
    var e = document.createEvent("HTMLEvents");
    e.initEvent(type, true, true);
    el.dispatchEvent(e);
  }
  function locateNode(vnode) {
    return vnode.componentInstance && (!vnode.data || !vnode.data.transition) ? locateNode(vnode.componentInstance._vnode) : vnode;
  }
  var show = {
    bind: function(el, _a, vnode) {
      var value = _a.value;
      vnode = locateNode(vnode);
      var transition2 = vnode.data && vnode.data.transition;
      var originalDisplay = el.__vOriginalDisplay = el.style.display === "none" ? "" : el.style.display;
      if (value && transition2) {
        vnode.data.show = true;
        enter(vnode, function() {
          el.style.display = originalDisplay;
        });
      } else {
        el.style.display = value ? originalDisplay : "none";
      }
    },
    update: function(el, _a, vnode) {
      var value = _a.value, oldValue = _a.oldValue;
      if (!value === !oldValue)
        return;
      vnode = locateNode(vnode);
      var transition2 = vnode.data && vnode.data.transition;
      if (transition2) {
        vnode.data.show = true;
        if (value) {
          enter(vnode, function() {
            el.style.display = el.__vOriginalDisplay;
          });
        } else {
          leave(vnode, function() {
            el.style.display = "none";
          });
        }
      } else {
        el.style.display = value ? el.__vOriginalDisplay : "none";
      }
    },
    unbind: function(el, binding, vnode, oldVnode, isDestroy) {
      if (!isDestroy) {
        el.style.display = el.__vOriginalDisplay;
      }
    }
  };
  var platformDirectives = {
    model: directive,
    show
  };
  var transitionProps = {
    name: String,
    appear: Boolean,
    css: Boolean,
    mode: String,
    type: String,
    enterClass: String,
    leaveClass: String,
    enterToClass: String,
    leaveToClass: String,
    enterActiveClass: String,
    leaveActiveClass: String,
    appearClass: String,
    appearActiveClass: String,
    appearToClass: String,
    duration: [Number, String, Object]
  };
  function getRealChild(vnode) {
    var compOptions = vnode && vnode.componentOptions;
    if (compOptions && compOptions.Ctor.options.abstract) {
      return getRealChild(getFirstComponentChild(compOptions.children));
    } else {
      return vnode;
    }
  }
  function extractTransitionData(comp) {
    var data = {};
    var options = comp.$options;
    for (var key in options.propsData) {
      data[key] = comp[key];
    }
    var listeners = options._parentListeners;
    for (var key in listeners) {
      data[camelize(key)] = listeners[key];
    }
    return data;
  }
  function placeholder(h2, rawChild) {
    if (/\d-keep-alive$/.test(rawChild.tag)) {
      return h2("keep-alive", {
        props: rawChild.componentOptions.propsData
      });
    }
  }
  function hasParentTransition(vnode) {
    while (vnode = vnode.parent) {
      if (vnode.data.transition) {
        return true;
      }
    }
  }
  function isSameChild(child, oldChild) {
    return oldChild.key === child.key && oldChild.tag === child.tag;
  }
  var isNotTextNode = function(c) {
    return c.tag || isAsyncPlaceholder(c);
  };
  var isVShowDirective = function(d) {
    return d.name === "show";
  };
  var Transition = {
    name: "transition",
    props: transitionProps,
    abstract: true,
    render: function(h2) {
      var _this = this;
      var children = this.$slots.default;
      if (!children) {
        return;
      }
      children = children.filter(isNotTextNode);
      if (!children.length) {
        return;
      }
      var mode = this.mode;
      var rawChild = children[0];
      if (hasParentTransition(this.$vnode)) {
        return rawChild;
      }
      var child = getRealChild(rawChild);
      if (!child) {
        return rawChild;
      }
      if (this._leaving) {
        return placeholder(h2, rawChild);
      }
      var id = "__transition-".concat(this._uid, "-");
      child.key = child.key == null ? child.isComment ? id + "comment" : id + child.tag : isPrimitive(child.key) ? String(child.key).indexOf(id) === 0 ? child.key : id + child.key : child.key;
      var data = (child.data || (child.data = {})).transition = extractTransitionData(this);
      var oldRawChild = this._vnode;
      var oldChild = getRealChild(oldRawChild);
      if (child.data.directives && child.data.directives.some(isVShowDirective)) {
        child.data.show = true;
      }
      if (oldChild && oldChild.data && !isSameChild(child, oldChild) && !isAsyncPlaceholder(oldChild) && // #6687 component root is a comment node
      !(oldChild.componentInstance && oldChild.componentInstance._vnode.isComment)) {
        var oldData = oldChild.data.transition = extend({}, data);
        if (mode === "out-in") {
          this._leaving = true;
          mergeVNodeHook(oldData, "afterLeave", function() {
            _this._leaving = false;
            _this.$forceUpdate();
          });
          return placeholder(h2, rawChild);
        } else if (mode === "in-out") {
          if (isAsyncPlaceholder(child)) {
            return oldRawChild;
          }
          var delayedLeave_1;
          var performLeave = function() {
            delayedLeave_1();
          };
          mergeVNodeHook(data, "afterEnter", performLeave);
          mergeVNodeHook(data, "enterCancelled", performLeave);
          mergeVNodeHook(oldData, "delayLeave", function(leave2) {
            delayedLeave_1 = leave2;
          });
        }
      }
      return rawChild;
    }
  };
  var props = extend({
    tag: String,
    moveClass: String
  }, transitionProps);
  delete props.mode;
  var TransitionGroup = {
    props,
    beforeMount: function() {
      var _this = this;
      var update = this._update;
      this._update = function(vnode, hydrating) {
        var restoreActiveInstance = setActiveInstance(_this);
        _this.__patch__(
          _this._vnode,
          _this.kept,
          false,
          // hydrating
          true
          // removeOnly (!important, avoids unnecessary moves)
        );
        _this._vnode = _this.kept;
        restoreActiveInstance();
        update.call(_this, vnode, hydrating);
      };
    },
    render: function(h2) {
      var tag = this.tag || this.$vnode.data.tag || "span";
      var map = /* @__PURE__ */ Object.create(null);
      var prevChildren = this.prevChildren = this.children;
      var rawChildren = this.$slots.default || [];
      var children = this.children = [];
      var transitionData = extractTransitionData(this);
      for (var i = 0; i < rawChildren.length; i++) {
        var c = rawChildren[i];
        if (c.tag) {
          if (c.key != null && String(c.key).indexOf("__vlist") !== 0) {
            children.push(c);
            map[c.key] = c;
            (c.data || (c.data = {})).transition = transitionData;
          }
        }
      }
      if (prevChildren) {
        var kept = [];
        var removed = [];
        for (var i = 0; i < prevChildren.length; i++) {
          var c = prevChildren[i];
          c.data.transition = transitionData;
          c.data.pos = c.elm.getBoundingClientRect();
          if (map[c.key]) {
            kept.push(c);
          } else {
            removed.push(c);
          }
        }
        this.kept = h2(tag, null, kept);
        this.removed = removed;
      }
      return h2(tag, null, children);
    },
    updated: function() {
      var children = this.prevChildren;
      var moveClass = this.moveClass || (this.name || "v") + "-move";
      if (!children.length || !this.hasMove(children[0].elm, moveClass)) {
        return;
      }
      children.forEach(callPendingCbs);
      children.forEach(recordPosition);
      children.forEach(applyTranslation);
      this._reflow = document.body.offsetHeight;
      children.forEach(function(c) {
        if (c.data.moved) {
          var el_1 = c.elm;
          var s = el_1.style;
          addTransitionClass(el_1, moveClass);
          s.transform = s.WebkitTransform = s.transitionDuration = "";
          el_1.addEventListener(transitionEndEvent, el_1._moveCb = function cb(e) {
            if (e && e.target !== el_1) {
              return;
            }
            if (!e || /transform$/.test(e.propertyName)) {
              el_1.removeEventListener(transitionEndEvent, cb);
              el_1._moveCb = null;
              removeTransitionClass(el_1, moveClass);
            }
          });
        }
      });
    },
    methods: {
      hasMove: function(el, moveClass) {
        if (!hasTransition) {
          return false;
        }
        if (this._hasMove) {
          return this._hasMove;
        }
        var clone = el.cloneNode();
        if (el._transitionClasses) {
          el._transitionClasses.forEach(function(cls) {
            removeClass(clone, cls);
          });
        }
        addClass(clone, moveClass);
        clone.style.display = "none";
        this.$el.appendChild(clone);
        var info = getTransitionInfo(clone);
        this.$el.removeChild(clone);
        return this._hasMove = info.hasTransform;
      }
    }
  };
  function callPendingCbs(c) {
    if (c.elm._moveCb) {
      c.elm._moveCb();
    }
    if (c.elm._enterCb) {
      c.elm._enterCb();
    }
  }
  function recordPosition(c) {
    c.data.newPos = c.elm.getBoundingClientRect();
  }
  function applyTranslation(c) {
    var oldPos = c.data.pos;
    var newPos = c.data.newPos;
    var dx = oldPos.left - newPos.left;
    var dy = oldPos.top - newPos.top;
    if (dx || dy) {
      c.data.moved = true;
      var s = c.elm.style;
      s.transform = s.WebkitTransform = "translate(".concat(dx, "px,").concat(dy, "px)");
      s.transitionDuration = "0s";
    }
  }
  var platformComponents = {
    Transition,
    TransitionGroup
  };
  Vue.config.mustUseProp = mustUseProp;
  Vue.config.isReservedTag = isReservedTag;
  Vue.config.isReservedAttr = isReservedAttr;
  Vue.config.getTagNamespace = getTagNamespace;
  Vue.config.isUnknownElement = isUnknownElement;
  extend(Vue.options.directives, platformDirectives);
  extend(Vue.options.components, platformComponents);
  Vue.prototype.__patch__ = inBrowser ? patch : noop;
  Vue.prototype.$mount = function(el, hydrating) {
    el = el && inBrowser ? query(el) : void 0;
    return mountComponent(this, el, hydrating);
  };
  if (inBrowser) {
    setTimeout(function() {
      if (config.devtools) {
        if (devtools) {
          devtools.emit("init", Vue);
        }
      }
    }, 0);
  }
  function normalizeComponent(scriptExports, render, staticRenderFns, functionalTemplate, injectStyles, scopeId, moduleIdentifier, shadowMode) {
    var options = typeof scriptExports === "function" ? scriptExports.options : scriptExports;
    if (render) {
      options.render = render;
      options.staticRenderFns = staticRenderFns;
      options._compiled = true;
    }
    if (scopeId) {
      options._scopeId = "data-v-" + scopeId;
    }
    return {
      exports: scriptExports,
      options
    };
  }
  const _sfc_main$7 = {
    name: "stackedComponent",
    mounted: function() {
      if ("sticky" in wcsvi.options && this.boolEval(wcsvi.options.sticky) && this.windowType == "desktop") {
        this.buildSticky();
      }
      this.$bus.$on("vidPlayerStack_" + this.$root.$children[0].pid, (index2) => {
        let $li = $jq(this.$el).find("li:eq(" + index2 + ")").closest("li");
        let $class = ".svi-player_" + this.list[index2].id + "_" + this.list[index2].idk + "_" + this.$el.className;
        $li.find($class).remove();
        $li.find("img,.sviicon-play").show();
        this.$bus.$emit("sviEmit", "pos_vidPlayerStack");
      });
      this.$bus.$on(
        "thumbImages_static_" + this.$root.$children[0].pid,
        (imgObj) => {
          if ("status" in imgObj) imgObj = imgObj.gal;
          if (!this.isEquivalent(this.list, imgObj)) {
            this.list = [];
            setTimeout(() => {
              this.list = imgObj;
              if ("sticky" in wcsvi.options && this.boolEval(wcsvi.options.sticky) && this.windowType == "desktop") {
                this.goSticky();
              }
            }, 200);
          }
          this.$bus.$emit("sviEmit", "pos_thumbImages_static");
        }
      );
      this.$nextTick(function() {
        window.addEventListener("resize", () => {
          this.getWindowWidth();
        });
        this.getWindowWidth();
      });
    },
    data() {
      let $cols = "stacked_columns" in wcsvi.options && wcsvi.options.stacked_columns ? wcsvi.options.stacked_columns : 1;
      return {
        columns: $cols,
        columns_original: $cols,
        list: [],
        windowType: this.getWindowWidth(true),
        activateTitle: "imagecaption" in wcsvi.options && this.boolEval(wcsvi.options.imagecaption) ? true : false,
        imagecaption_option: "imagecaption_option" in wcsvi.options && wcsvi.options.imagecaption_option ? wcsvi.options.imagecaption_option : "",
        thumbtype: "svimainthumb"
      };
    },
    methods: {
      buildSticky() {
        if ("sa_element" in wcsvi.options) {
          if ($jq(wcsvi.options.sa_element).length > 0) {
            $jq(wcsvi.options.sa_element).wrapInner(function() {
              return '<div class="stickysvi" style="top:' + wcsvi.options.sticky_margin + 'px;"></div>';
            });
          }
        }
      },
      goSticky() {
        if ($jq(".stickysvi").length > 0) {
          $jq(".stickysvi").stickThis({
            top: "sticky_margin" in wcsvi.options && wcsvi.options.sticky_margin ? wcsvi.options.sticky_margin : 0,
            pushup: "sa_pushup" in wcsvi.options && wcsvi.options.sa_pushup ? wcsvi.options.sa_pushup : ""
          });
        }
      }
    }
  };
  var _sfc_render$7 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "svi-stackedsection" }, [_c("ul", { staticClass: "svistacked svithumbnails", class: ["columns-" + _vm.columns] }, _vm._l(_vm.list, function(item, index2) {
      return _c("li", { directives: [{ name: "ezPlusStacked", rawName: "v-ezPlusStacked" }, { name: "playVideo", rawName: "v-playVideo", value: item, expression: "item" }], key: item.id + "_" + index2, class: _vm.colposition(index2), domProps: { "innerHTML": _vm._s(_vm.imgEl(item, "stack")) }, on: { "click": function($event) {
        return _vm.photo(item, $event, "stack");
      } } });
    }), 0)]);
  };
  var _sfc_staticRenderFns$7 = [];
  var __component__$7 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$7,
    _sfc_render$7,
    _sfc_staticRenderFns$7,
    false,
    null,
    null
  );
  const stackedComponent = __component__$7.exports;
  const _sfc_main$6 = {
    components: {},
    name: "SliderComponent",
    mounted: function() {
      if (!this.disable_thumb) this.galleryThumbs = this.sliderThumbs();
      this.galleryTop = this.sliderMain();
      this.thumbImages_static();
      this.mainImage_slider_thumbadd();
    },
    data() {
      return {
        statusThumbs: false,
        originalCount: 0,
        imgTitle: "",
        mainTitle: "imagecaption" in wcsvi.options && this.boolEval(wcsvi.options.imagecaption) ? true : false,
        thumbTitle: "thumb_imagecaption" in wcsvi.options && this.boolEval(wcsvi.options.thumb_imagecaption) ? true : false,
        slides: [],
        disable_thumb: "disable_thumb" in wcsvi.options && this.boolEval(wcsvi.options.disable_thumb) ? true : false,
        galleryThumbs: false,
        galleryTop: false,
        thumbAppend: false,
        slider_pagination: "slider_pagination" in wcsvi.options && this.boolEval(wcsvi.options.slider_pagination) ? true : false,
        slider_pagination_color: {
          "--paginationColor": "slider_pagination_color" in wcsvi.options && wcsvi.options.slider_pagination_color ? wcsvi.options.slider_pagination_color : ""
        },
        slider_navigation: "slider_navigation" in wcsvi.options && this.boolEval(wcsvi.options.slider_navigation) ? true : false,
        slider_navigation_thumb: "slider_navigation_thumb" in wcsvi.options && this.boolEval(wcsvi.options.slider_navigation_thumb) ? true : false,
        slider_navigation_color: {
          "--sviproslider_navcolor": "slider_navigation_color" in wcsvi.options && wcsvi.options.slider_navigation_color ? wcsvi.options.slider_navigation_color : ""
        },
        slider_navcolor: "slider_navcolor" in wcsvi.options && wcsvi.options.slider_navcolor ? wcsvi.options.slider_navcolor : "",
        lazyLoadHtml: "slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload) ? '<div class="swiper-lazy-preloader" style="--lazyloadColor:' + wcsvi.options.slider_lazyload_color + '"></div>' : ""
      };
    },
    methods: {
      thumbImages_static() {
        this.$bus.$on(
          "vidPlayerSlider_" + this.$root.$children[0].pid,
          (index2) => {
            this.vidRun(index2, this.slides);
            this.$bus.$emit("sviEmit", "vidPlayerSlider");
          }
        );
        this.$bus.$on(
          "thumbImages_static_" + this.$root.$children[0].pid,
          (imgObj) => {
            if (this.thumbAppend) {
              this.galleryTop.removeSlide(0);
              if (!this.disable_thumb) this.galleryThumbs.removeSlide(0);
              this.thumbAppend = false;
            }
            if (!this.isEquivalent(this.slides, imgObj.gal)) {
              let start = "";
              this.originalCount = this.slides.length;
              this.statusThumbs = imgObj.status;
              this.slides = imgObj.gal;
              $jq.each(imgObj.gal, (i, v2) => {
                v2.sviid = v2.id;
                let img = this.imgEl(v2, "slider");
                let ratio = this.calculateAspectRatioFit(v2);
                let hH = 'style="min-height:' + ratio.height + 'px"';
                this.galleryTop.appendSlide(
                  '<div class="swiper-slide' + start + '" ' + (!v2.video ? hH : "") + ">" + img + (!v2.video ? this.lazyLoadHtml : "") + "</div>"
                );
                this.galleryTop.update();
                if (!this.disable_thumb) {
                  let img_thumb = this.imgEl(v2, "sliderthumb");
                  let thumbTitle = "";
                  if (this.thumbTitle)
                    thumbTitle = '<div class="svititleimg">' + this.returnTitle(
                      wcsvi.options.thumb_imagecaption_option,
                      v2
                    ) + "</div>";
                  this.galleryThumbs.appendSlide(
                    '<div class="swiper-slide' + start + '">' + img_thumb + this.lazyLoadHtml + thumbTitle + "</div>"
                  );
                  this.galleryThumbs.update();
                  if (i == 0) this.runOpacity();
                }
              });
              if ("slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload))
                this.completeLoad();
              else this.waitload(".svi-gallery-top");
            } else {
              if ("skip_equivalent" in wcsvi.options && this.boolEval(wcsvi.options.skip_equivalent))
                this.galleryTop.slideTo(0);
              if (this.$root.$children[0].originalAttributes && "skip_equivalent" in wcsvi.options && !this.boolEval(wcsvi.options.skip_equivalent)) {
                this.runKeepThumbnailsImageChange(
                  this.$root.$children[0].originalAttributes,
                  true
                );
              }
            }
            this.$bus.$emit("sviEmit", "pos_thumbImages_static");
          }
        );
      },
      mainImage_slider_thumbadd() {
        this.$bus.$on(
          "mainImage_slider_thumbadd_" + this.$root.$children[0].pid,
          (imgObj) => {
            if (this.thumbAppend) {
              this.galleryTop.removeSlide(0);
              if (!this.disable_thumb) this.galleryThumbs.removeSlide(0);
            }
            let isImgInSlider = this.isImgInSlider(imgObj);
            if (isImgInSlider != "false") {
              this.galleryTop.slideTo(isImgInSlider);
              this.thumbAppend = false;
              return;
            }
            v.class = "swiper-lazy";
            v.sviid = imgObj.id;
            let img = this.imgEl(imgObj, "slider");
            this.galleryTop.appendSlide(
              '<div class="swiper-slide">' + img + this.lazyLoadHtml + "</div>"
            );
            this.galleryTop.update();
            if (!this.disable_thumb) {
              v.class = "swiper-lazy";
              v.sviid = imgObj.id;
              let img_thumb = this.imgEl(imgObj, "sliderthumb");
              this.galleryThumbs.appendSlide(
                '<div class="swiper-slide">' + img_thumb + this.lazyLoadHtml + "</div>"
              );
              this.galleryThumbs.update();
            }
            this.galleryTop.slideTo(0);
            this.thumbAppend = true;
            this.$bus.$emit("sviEmit", "pos_mainImage_slider_thumbadd");
          }
        );
      },
      isImgInSlider(imgObj) {
        let imgID = imgObj.id;
        let result = $jq.map(this.galleryTop.slides, ($slide, i) => {
          let slideImgId = $jq($slide).find("img").data("sviid");
          if (slideImgId == imgID) return i;
        });
        if (result.length > 0) return result[0];
        else return "false";
      },
      setHeight() {
        if (!this.disable_thumb && !this.statusThumbs && parseInt(wcsvi.options.slider_position) != 0) {
          setTimeout(() => {
            this.galleryThumbs.updateAutoHeight();
            this.galleryThumbs.updateSlides();
          }, 100);
        }
      },
      waitload($class) {
        let _this = this;
        $jq($class).imagesLoaded().done(function(instance) {
          _this.completeLoad();
        });
      },
      completeLoad() {
        if (this.originalCount > 0) {
          this.removeSlides();
        } else {
          this.handleUpdate();
        }
      },
      removeSlides() {
        var i;
        var remove2 = [];
        for (i = 0; i < this.originalCount; i++) {
          remove2.push(i);
        }
        this.galleryTop.removeSlide(remove2);
        if (!this.disable_thumb) this.galleryThumbs.removeSlide(remove2);
        setTimeout(() => {
          this.handleUpdate();
        }, 50);
      },
      handleUpdate() {
        if ("slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload)) {
          this.galleryTop.update();
          if (!this.disable_thumb) {
            this.galleryThumbs.update();
          }
        }
        this.vidRun(0, this.slides);
        this.setHeight();
        this.$forceUpdate();
        if (this.mainTitle) this.runInsertTitle(this.galleryTop.realIndex);
        if (this.$root.$children[0].originalAttributes && "skip_equivalent" in wcsvi.options && !this.boolEval(wcsvi.options.skip_equivalent)) {
          this.runKeepThumbnailsImageChange(
            this.$root.$children[0].originalAttributes,
            true
          );
        } else this.galleryTop.slideTo(0, 500);
      }
    }
  };
  var _sfc_render$6 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "sviproslider" }, [_c("div", { staticClass: "swiper svi-gallery-top" }, [_c("div", { directives: [{ name: "ezPlusSlider", rawName: "v-ezPlusSlider" }], staticClass: "swiper-wrapper" }), _vm.slider_pagination ? _c("div", { staticClass: "swiper-pagination sviswiper-pagination", style: _vm.slider_pagination_color }) : _vm._e(), _vm.slider_navigation ? _c("div", { staticClass: "swiper-button-next swiperTop-swiper-button-next", class: ["swiper-button" + _vm.slider_navcolor], style: [
      _vm.slider_navcolor == "custom" ? _vm.slider_navigation_color : ""
    ], attrs: { "slot": "button-next" }, slot: "button-next" }) : _vm._e(), _vm.slider_navigation ? _c("div", { staticClass: "swiper-button-prev swiperTop-swiper-button-prev", class: ["swiper-button" + _vm.slider_navcolor], style: [
      _vm.slider_navcolor == "custom" ? _vm.slider_navigation_color : ""
    ], attrs: { "slot": "button-prev" }, slot: "button-prev" }) : _vm._e()]), _vm.mainTitle ? _c("div", { staticClass: "svititleimg gallery-top-title", domProps: { "innerHTML": _vm._s(_vm.imgTitle) } }) : _vm._e(), !_vm.disable_thumb ? _c("div", { directives: [{ name: "show", rawName: "v-show", value: !_vm.statusThumbs, expression: "!statusThumbs" }], staticClass: "swiper svi-gallery-thumbs" }, [_c("div", { staticClass: "swiper-wrapper" }), _vm.slider_navigation_thumb ? _c("div", { staticClass: "swiper-button-next swiperThumbs-swiper-button-next", class: "swiper-button" + _vm.slider_navcolor, style: [
      _vm.slider_navcolor == "custom" ? _vm.slider_navigation_color : ""
    ] }) : _vm._e(), _vm.slider_navigation_thumb ? _c("div", { staticClass: "swiper-button-prev swiperThumbs-swiper-button-prev", class: "swiper-button" + _vm.slider_navcolor, style: [
      _vm.slider_navcolor == "custom" ? _vm.slider_navigation_color : ""
    ] }) : _vm._e()]) : _vm._e()]);
  };
  var _sfc_staticRenderFns$6 = [];
  var __component__$6 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$6,
    _sfc_render$6,
    _sfc_staticRenderFns$6,
    false,
    null,
    null
  );
  const sliderComponent = __component__$6.exports;
  const _sfc_main$5 = {
    name: "imageComponent",
    watch: {
      image: function(val) {
        setTimeout(() => {
          if ("video" in wcsvi.options) this.plyr(val);
        }, 100);
      }
    },
    mounted: function() {
      this.$bus.$on(
        "mainImage_static_" + this.$root.$children[0].pid,
        (imgObj) => {
          if (!this.isEquivalent(this.gallery, imgObj)) {
            this.gallery = imgObj;
            this.image = imgObj[0];
            if (this.image.video == "") {
              this.ratio = this.calculateAspectRatioFit();
              this.imgStyle = {
                "min-height": this.ratio.height + "px"
              };
            } else {
              this.imgStyle = {
                "min-height": "auto"
              };
            }
            this.$bus.$emit("sviEmit", "pos_mainImage_static");
          }
          this.$bus.$emit("sviEmit", "mainImage_complete");
        }
      );
      this.$bus.$on(
        "mainImage_static_change_" + this.$root.$children[0].pid,
        (imgObj) => {
          this.image = imgObj;
          if (this.image.video == "") {
            this.ratio = this.calculateAspectRatioFit();
            this.imgStyle = {
              "min-height": this.ratio.height + "px"
            };
          } else {
            this.imgStyle = {
              "min-height": "auto"
            };
          }
          this.$bus.$emit("sviEmit", "pos_mainImage_static_change");
        }
      );
      this.$bus.$on(
        "mainImage_static_changeRestore_" + this.$root.$children[0].pid,
        (imgObj) => {
          if (!imgObj && !this.notPresent()) {
            this.image = this.gallery[0];
            this.$bus.$emit("sviEmit", "pos_mainImage_static_changeRestore");
          }
        }
      );
      if (this.$root.$children[0].form) {
        this.$root.$children[0].form.on("click", ".reset_variations", () => {
          this.image = this.gallery[0];
        });
      }
    },
    data: function() {
      return {
        gallery: [],
        image: "",
        imgStyle: {},
        pic: false,
        ratio: {},
        activateTitle: "imagecaption" in wcsvi.options && this.boolEval(wcsvi.options.imagecaption) ? true : false
      };
    },
    updated: function() {
      this.sviLog("Gallery", "mainImageGal DOM Updated (Render Complete)", { time: performance.now() });
    },
    methods: {
      notPresent() {
        let result = false;
        $jq.each(this.gallery, (i, $image) => {
          if (this.isEquivalent(this.image, $image)) {
            result = true;
          }
        });
        return result;
      },
      waitload($class) {
        $jq(this.$el).imagesLoaded().done(function(instance) {
          $jq(this.$el).removeAttr("style");
        });
      }
    }
  };
  var _sfc_render$5 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { directives: [{ name: "ezPlus", rawName: "v-ezPlus" }], staticClass: "svi-mainsection", style: _vm.imgStyle, domProps: { "innerHTML": _vm._s(_vm.imgEl(_vm.image, "static")) }, on: { "click": function($event) {
      return _vm.photo(_vm.image, $event, "static");
    } } });
  };
  var _sfc_staticRenderFns$5 = [];
  var __component__$5 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$5,
    _sfc_render$5,
    _sfc_staticRenderFns$5,
    false,
    null,
    null
  );
  const mainImageGalComponent = __component__$5.exports;
  const _sfc_main$4 = {
    name: "ThumbnailComponent",
    mounted: function() {
      this.$bus.$on(
        this.cbus_event + "_" + this.$root.$children[0].pid,
        (imgObj) => {
          if (!this.isEquivalent(this.list, imgObj)) {
            this.list = imgObj;
            this.$bus.$emit("sviEmit", "pos_" + this.cbus_event);
            if (this.$root.$children[0].originalAttributes) {
              this.runKeepThumbnailsImageChange(
                this.$root.$children[0].originalAttributes
              );
            }
          } else {
            if (this.$root.$children[0].originalAttributes) {
              this.runKeepThumbnailsImageChange(
                this.$root.$children[0].originalAttributes
              );
            }
          }
        }
      );
    },
    data() {
      let $cols = parseInt(wcsvi.options.columns);
      let $cols_showcase = "columns_showcase" in wcsvi.options && parseInt(wcsvi.options.columns_showcase) ? parseInt(wcsvi.options.columns_showcase) : 4;
      if ("slider_position" in wcsvi.options) {
        if (this.$root.$children[0].$options.name == "main-svi" && parseInt(wcsvi.options.slider_position) != 0) {
          $cols = 1;
        }
      }
      let thumbtype = this.$root.$children[0].$options.name == "main-svi" ? "svimainthumb" : "svivariationsthumb";
      let cbus_event = thumbtype == "svimainthumb" ? "thumbImages_static" : "variationThumbImages_static";
      return {
        columns: thumbtype == "svimainthumb" ? $cols : $cols_showcase,
        list: [],
        cbus_event,
        thumbtype,
        activateTitle: "thumb_imagecaption" in wcsvi.options && this.boolEval(wcsvi.options.thumb_imagecaption) ? true : false
      };
    },
    updated: function() {
      this.sviLog("Thumbnails", "thumbnails DOM Updated (Render Complete)", { time: performance.now() });
    },
    methods: {
      triggerChange: function(item, $event) {
        if (this.thumbtype == "svimainthumb") {
          this.runOpacity($event);
          this.$bus.$emit(
            "mainImage_static_change_" + this.$root.$children[0].pid,
            item
          );
          if ("slider" in wcsvi.options) {
            if (this.boolEval(wcsvi.options.slider))
              this.$bus.$emit(
                "mainImage_slider_thumbadd_" + this.$root.$children[0].pid,
                item
              );
          }
          if ("variation_swap" in wcsvi.options) {
            if (this.boolEval(wcsvi.options.variation_swap))
              this.ThumbClickSwap(item);
          }
        } else {
          if ("variation_thumbnails_lb" in wcsvi.options) {
            if (this.boolEval(wcsvi.options.variation_thumbnails_lb) || item.video)
              this.photo(item, $event, "variationThumbImages");
            else {
              if ($jq(this.$el).find("video,.plyr").length > 0) {
                $jq(this.$el).find("video,.plyr").remove();
                $jq(this.$el).find(".sviicon-play").show();
              }
            }
          }
        }
      }
    }
  };
  var _sfc_render$4 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "svi-thumbsection", class: _vm.thumbtype }, [_c("ul", { key: "svithumbnails", staticClass: "svithumbnails", class: [
      "columns-" + _vm.columns,
      _vm.list.length < 1 ? "svi100" : "",
      _vm.list.length > 0 ? "svishow" : ""
    ] }, _vm._l(_vm.list, function(item, index2) {
      return _c("li", { key: item.id + "_" + index2, class: _vm.colposition(index2), domProps: { "innerHTML": _vm._s(_vm.imgEl(item, _vm.thumbtype)) }, on: { "click": function($event) {
        return _vm.triggerChange(item, $event);
      } } });
    }), 0)]);
  };
  var _sfc_staticRenderFns$4 = [];
  var __component__$4 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$4,
    _sfc_render$4,
    _sfc_staticRenderFns$4,
    false,
    null,
    "eca8d2b6"
  );
  const thumbnailsComponent = __component__$4.exports;
  const _sfc_main$3 = {
    components: {
      mainImageGalComponent,
      thumbnailsComponent
    },
    name: "StaticComponent",
    mounted: function() {
    },
    data() {
      return {
        disabledThumbs: false in wcsvi.options || this.boolEval(wcsvi.options.disable_thumb) ? false : true
      };
    }
  };
  var _sfc_render$3 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "svistatic" }, [_c("mainImageGalComponent"), _vm.disabledThumbs ? _c("thumbnailsComponent") : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$3 = [];
  var __component__$3 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$3,
    _sfc_render$3,
    _sfc_staticRenderFns$3,
    false,
    null,
    null
  );
  const staticComponent = __component__$3.exports;
  const _sfc_main$2 = {
    components: {
      stackedComponent,
      sliderComponent,
      staticComponent
    },
    props: ["pid", "form", "ptype", "wcsvi", "ajax"],
    name: "main-svi",
    watch: {
      windowType: function(val) {
        if (this.boolEval(this.options.stacked)) {
          setTimeout(() => {
            this.loadSviGalleries();
          }, 150);
        }
      }
    },
    mounted: function() {
      const wrapper = this.$el && this.$el.closest ? this.$el.closest(".svi_wrapper") : null;
      if (wrapper) {
        wrapper.classList.add("svi-app-mounted");
        this.cleanupPlaceholder(wrapper);
      }
      if (this.form.length > 0 && parseInt(wcsvi.options.default_swatches)) {
        this.form.addClass("swatches-support");
        this.buildSwatches();
      } else if (parseInt(wcsvi.options.default_swatches)) {
        this.buildSwatches();
      }
      if (!this.hasImages) return;
      if (this.ajax) this.loadSviGalleries();
      if (this.ptype && (this.hasSVIdata || this.hasNativeVariationGalleries)) this.DetectChangesInForm();
      else this.loadSviGalleries();
      this.$nextTick(function() {
        window.addEventListener("resize", () => {
          this.getWindowWidth();
        });
        this.getWindowWidth();
        if ($jq(".gallery-svi").css("opacity") == 0)
          $jq(".gallery-svi").css("opacity", 1);
        const wrapperEl = this.$el && this.$el.closest ? this.$el.closest(".svi_wrapper") : null;
        if (wrapperEl) {
          const markReady = () => {
            wrapperEl.classList.add("svi-app-ready");
            this.cleanupPlaceholder(wrapperEl);
          };
          if (typeof window.requestAnimationFrame === "function") {
            window.requestAnimationFrame(() => {
              window.requestAnimationFrame(markReady);
            });
          } else {
            setTimeout(markReady, 50);
          }
        }
      });
      this.$bus.$on("vidPlayer_" + this.$root.$children[0].pid, (vidObj) => {
        if (!this.curVideo) {
          this.curVideo = vidObj;
        } else {
          if (this.curVideo.playing) {
            this.curVideo.stop();
            this.curVideo.destroy();
            this.curVideo = false;
          }
          if (vidObj) {
            this.curVideo = vidObj;
          } else {
            this.curVideo = false;
          }
        }
        this.$bus.$emit("sviEmit", "pos_vidPlayer");
      });
    },
    computed: {
      variations_form: function() {
        return this.form;
      },
      attributeFields: function() {
        return this.variations_form.find(".variations select");
      },
      singleVariation: function() {
        return this.variations_form.find(".single_variation");
      },
      resetVariations: function() {
        return this.variations_form.find(".reset_variations");
      },
      variationData: function() {
        return this.variations_form.data("product_variations");
      },
      sviflex() {
        if ("slider_position" in wcsvi.options) {
          var thumbpos;
          if (wcsvi.options.slider_position == "1") thumbpos = " sviflex-l";
          if (wcsvi.options.slider_position == "2") thumbpos = " sviflex-r";
          return wcsvi.options.slider_position == "0" ? "svi-horizontal" : "sviflex" + thumbpos;
        } else {
          return "svi-horizontal";
        }
      }
    },
    data: function() {
      window.wcsvislugs = "slugs" in this.wcsvi && Object.keys(this.wcsvi.slugs).length > 0 ? this.wcsvi.slugs : [];
      return {
        hasImages: "images" in this.wcsvi && this.wcsvi.images.length > 0 ? true : false,
        type: "static",
        options: wcsvi.options,
        gallery: this.wcsvi,
        attributes: false,
        template: "svi-" + wcsvi.options.template,
        windowType: this.getWindowWidth(true),
        curVideo: false,
        hasSVIdata: "slugs" in this.wcsvi && Object.keys(this.wcsvi.slugs).length > 0 ? true : false,
        hasNativeVariationGalleries: "variation_galleries" in this.wcsvi && Object.keys(this.wcsvi.variation_galleries).length > 0 ? true : false,
        currentVariationId: 0,
        originalAttributes: "keep_thumbnails" in wcsvi.options && parseInt(wcsvi.options.keep_thumbnails) ? [] : false
      };
    },
    methods: {
      cleanupPlaceholder(wrapperEl) {
        const holder = wrapperEl.querySelector(".svi-initial-holder");
        if (holder) {
          holder.remove();
        }
      }
      /* async requestSviGalleries() {
              // Submit the form.
      
              let res = await axios.post(wcsvi.call, {
                action: "loadProduct",
                id: this.pid,
                //attribute: this.getChosenAttributes()
              });
      
              this.gallery = res.data;
      
              this.requestSviGalleries();
            },*/
    }
  };
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "woosvi_strap", class: [
      _vm.sviflex,
      _vm.template,
      _vm.boolEval(_vm.options.stacked) ? "svi-stacked-parent" : ""
    ] }, [_vm.boolEval(_vm.options.stacked) ? [_vm.boolEval(_vm.options.force_stacked) ? _c("stackedComponent") : _vm._e(), !_vm.boolEval(_vm.options.force_stacked) && _vm.windowType == "desktop" ? _c("stackedComponent") : _vm._e(), !_vm.boolEval(_vm.options.force_stacked) && !_vm.boolEval(_vm.options.slider) && _vm.windowType == "mobile" ? [_c("staticComponent")] : _vm._e(), !_vm.boolEval(_vm.options.force_stacked) && _vm.boolEval(_vm.options.slider) && _vm.windowType == "mobile" ? [_c("sliderComponent")] : _vm._e()] : _vm._e(), !_vm.boolEval(_vm.options.slider) && !_vm.boolEval(_vm.options.stacked) ? [_c("staticComponent")] : _vm._e(), _vm.boolEval(_vm.options.slider) && !_vm.boolEval(_vm.options.stacked) ? [_c("sliderComponent")] : _vm._e()], 2);
  };
  var _sfc_staticRenderFns$2 = [];
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2,
    false,
    null,
    null
  );
  const MAIN = __component__$2.exports;
  const _sfc_main$1 = {
    components: {
      thumbnailsComponent
    },
    props: ["pid", "form", "ptype"],
    name: "thumb-svi",
    data: function() {
      return {};
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "svi-drop-gallery" }, [_c("thumbnailsComponent")], 1);
  };
  var _sfc_staticRenderFns$1 = [];
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1,
    false,
    null,
    null
  );
  const THUMB = __component__$1.exports;
  Vue.prototype.$bus = new Vue();
  Vue.prototype.buildMainGallery = function(idx, v2, MAIN2, ajax) {
    let $parent = $jq(v2).parent();
    let $product_id = $parent.data("sviproduct_id");
    let $svidata = $parent.data("wcsvi");
    const $dataRef = $parent.data("wcsviRef");
    const mountTarget = $parent.find("[data-svi-mount]").get(0) || v2;
    if (!$svidata && typeof wcsvi !== "undefined" && wcsvi.products) {
      const refId = $dataRef || $product_id;
      if (refId && Object.prototype.hasOwnProperty.call(wcsvi.products, refId)) {
        $svidata = wcsvi.products[refId];
        $parent.data("wcsvi", $svidata);
      }
    }
    if (!$svidata && typeof window.wcsviProducts !== "undefined") {
      const refId = $dataRef || $product_id;
      if (refId && Object.prototype.hasOwnProperty.call(window.wcsviProducts, refId)) {
        $svidata = window.wcsviProducts[refId];
        $parent.data("wcsvi", $svidata);
      }
    }
    let $ptype = $parent.hasClass("svi-variable") ? true : false;
    let $form = $ptype ? this.findVariationsForm($product_id) : false;
    const startWithData = (svidata) => {
      let dataObj = {
        main: MAIN2,
        v: mountTarget,
        product_id: $product_id,
        form: $form,
        ptype: $ptype,
        svidata: svidata ? svidata : false,
        ajaxData: ajax
      };
      if ($form == "not_found") {
        Vue.prototype.waitForEl(".variations_form", this.startMount, 100, 50, dataObj);
      } else {
        this.startMount(dataObj);
      }
    };
    if ($svidata) {
      startWithData($svidata);
      return;
    }
    if (!$product_id) {
      startWithData(false);
      return;
    }
    this.loadProduct($product_id).then((response) => {
      if (response) {
        $parent.data("wcsvi", response);
      }
      startWithData(response);
    }).catch(() => {
      startWithData(false);
    });
  };
  Vue.prototype.loadProduct = function($pid) {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: wcsvi.call,
        type: "POST",
        data: {
          action: "sviloadProduct",
          id: $pid
        }
      }).done((response) => {
        if (response && response.success && response.data) {
          resolve(response.data);
          return;
        }
        resolve(false);
      }).fail((jqXHR, textStatus, errorThrown) => {
        reject(errorThrown || textStatus);
      });
    });
  };
  Vue.prototype.startMount = function(dataObj) {
    window.svi_method = new Vue({
      render: (h) => h(dataObj.main, {
        props: {
          pid: dataObj.product_id,
          form: dataObj.form,
          ptype: dataObj.ptype,
          wcsvi: dataObj.svidata,
          ajax: dataObj.ajaxData
        }
      })
    }).$mount(dataObj.v);
  };
  Vue.prototype.findVariationsForm = function(product_id) {
    if ($jq(".variations_form[data-product_id='" + product_id + "']").length > 1)
      return $jq(".variations_form[data-product_id='" + product_id + "']").first();
    else {
      if ($jq(".variations_form[data-product_id='" + product_id + "']").length > 0)
        return $jq(".variations_form[data-product_id='" + product_id + "']");
      else
        return "not_found";
    }
  };
  Vue.prototype.DetectChangesInForm = function() {
    let $attr = {
      data: false
    };
    this.form.on(
      "check_variations",
      () => {
        wcsvi.options.nothumbs = false;
        setTimeout(() => {
          let $chosenAttr = this.getChosenAttributes();
          if (this.isEquivalentAttributes($attr.data, $chosenAttr.data))
            return;
          $attr = $chosenAttr;
          this.attributes = $chosenAttr;
          if (!this.gallery)
            this.requestSviGalleries();
          else
            this.loadSviGalleries();
        }, 50);
      }
    );
    this.form.on("found_variation.svi", (event, variation) => {
      this.currentVariationId = variation && variation.variation_id ? parseInt(variation.variation_id, 10) : 0;
      this.loadSviGalleries();
    });
    this.form.on("reset_data.svi hide_variation.svi", () => {
      this.currentVariationId = 0;
    });
  };
  Vue.prototype.isEquivalentAttributes = function(a, b) {
    return JSON.stringify(a) === JSON.stringify(b);
  };
  Vue.prototype.getChosenAttributes = function() {
    var data = {};
    var count = 0;
    var chosen = 0;
    this.attributeFields.each(function() {
      var attribute_name = $jq(this).data("attribute_name") || $jq(this).attr("name");
      var value = $jq(this).val() || "";
      if (value.length > 0) {
        chosen++;
      }
      count++;
      data[attribute_name] = value;
    });
    return {
      "count": count,
      "chosenCount": chosen,
      "data": data
    };
  };
  Vue.prototype.loadSviGalleries = function() {
    let $gal = this.gallery.images;
    if (this.ptype)
      $gal = this.RetrieveGalleryForDisplay();
    let $status = this.boolEval(wcsvi.options.hide_thumbs) && $gal.length < 1 ? true : false;
    if ($status) {
      this.$bus.$emit("mainImage_" + this.type + "_" + this.pid, this.gallery.images);
    } else {
      this.$bus.$emit("mainImage_" + this.type + "_" + this.pid, $gal);
    }
    if ("slider" in wcsvi.options && this.boolEval(wcsvi.options.slider)) {
      this.$bus.$emit("thumbImages_" + this.type + "_" + this.pid, {
        gal: $status ? this.gallery.images : $gal,
        status: $status
      });
    } else {
      this.$bus.$emit("thumbImages_" + this.type + "_" + this.pid, $gal);
    }
    if (this.boolEval(wcsvi.options.variation_thumbnails)) {
      let $gal_thumb = this.RetrieveGalleryForDisplay(true);
      if ((!("status" in wcsvi.options) || !wcsvi.options.status) && $gal_thumb.length > 1)
        $gal_thumb = $gal_thumb.slice(1, 2);
      this.$bus.$emit("variationThumbImages_" + this.type + "_" + this.pid, $gal_thumb);
    }
  };
  Vue.prototype.RetrieveGalleryForDisplay = function($isthumbGal = false) {
    let $attributes = this.attributes;
    this.sviLog("Gallery", "Matching Started (RetrieveGalleryForDisplay)", $attributes);
    let $gal = this.removeDuplicatesID(this.RetrieveImagesAccordingToSelectedAttributes($attributes.data, $isthumbGal, false));
    if ($gal.length < 1) {
      $gal = this.RetrieveNativeVariationGallery();
    }
    if ($gal.length < 1) {
      $gal = this.removeDuplicatesID(this.RetrieveImagesAccordingToSelectedAttributes($attributes.data, $isthumbGal));
    }
    this.sviLog("Gallery", "Final Match Result", { matchedImages: $gal, count: $gal.length });
    if ("placeholder" in wcsvi.options && this.boolEval(wcsvi.options.placeholder) && $gal.length < 1 && this.attributes.chosenCount > 0) {
      $gal.push(this.placeholder());
    }
    let result = [];
    if ($isthumbGal) {
      if ($gal.length > 0 && $attributes.chosenCount > 0)
        result = $gal;
      else
        result = [];
    } else {
      if ($gal.length > 0)
        result = $gal;
      else {
        if (this.boolEval(wcsvi.options.hide_thumbs)) {
          wcsvi.options.nothumbs = true;
          result = [];
        } else {
          const defaultGallery = this.gallery.images.filter((img) => !img.idk);
          const source = defaultGallery.length > 0 ? defaultGallery : this.gallery.images;
          const seen = {};
          result = source.filter((img) => {
            const key = img.id + "|" + (img.video ? "1" : "0");
            if (seen[key]) return false;
            seen[key] = true;
            return true;
          });
        }
      }
    }
    return result;
  };
  Vue.prototype.RetrieveImagesAccordingToSelectedAttributes = function($attributes, $isthumbGal, $allowDefault = true) {
    this.sviLog("Gallery", "Evaluating Selected Attributes", { attributes: $attributes, gallery: this.gallery });
    let $clean_attributes = $jq.map($attributes, ($attribute) => {
      this.sviLog("Gallery", "Slugifying Attribute", $attribute);
      if ($attribute)
        return this.convertAttributesNames($attribute);
    });
    this.sviLog("Gallery", "Cleaned Attributes", $clean_attributes);
    let $possible_combinations = this.RetrieveAttributesCombinations($clean_attributes, $isthumbGal);
    this.sviLog("Gallery", "Generated Combinations", $possible_combinations);
    let $img_ids = this.RetrieveTheImagesID($possible_combinations);
    if ($allowDefault && $img_ids.length < 1) {
      $possible_combinations = ["svidefault"];
      $img_ids = this.RetrieveTheImagesID($possible_combinations);
      this.sviLog("Gallery", "Fallback To Default (No valid combinations)", $possible_combinations);
    }
    this.sviLog("Gallery", "Extracting Image IDs", $img_ids);
    return this.RetrieveTheImages($img_ids, $isthumbGal);
  };
  Vue.prototype.RetrieveNativeVariationGallery = function() {
    const galleries = this.gallery.variation_galleries;
    const imageIds = galleries && this.currentVariationId ? galleries[this.currentVariationId] : false;
    if (!Array.isArray(imageIds) || imageIds.length < 1) {
      return [];
    }
    return imageIds.reduce((images, imageId) => {
      const image = this.gallery.images.find(
        (candidate) => parseInt(candidate.id, 10) === parseInt(imageId, 10) && !candidate.idk
      );
      if (image) {
        images.push(image);
      }
      return images;
    }, []);
  };
  Vue.prototype.RetrieveImagesAccordingToSelectedOriginalAttributes = function(originalAttributes) {
    let $img_ids = this.RetrieveTheImagesID(originalAttributes);
    return this.RetrieveTheImages($img_ids);
  };
  Vue.prototype.RetrieveTheImages = function($img_ids, $isthumbGal) {
    return $jq.map(this.gallery.images, ($images) => {
      return $jq.map($img_ids, ($img_val, i) => {
        if (parseInt($img_val.id) == parseInt($images.id) && parseInt($img_val.k) == parseInt($images.idk)) {
          return $images;
        }
      });
    });
  };
  Vue.prototype.RetrieveTheImagesID = function($combinations) {
    return $jq.map(this.gallery.svi, (svi, i) => {
      let find = "wc_svimainvideo";
      if ("slugs" in svi)
        find = svi.slugs.join("_svipro_");
      this.sviLog("Gallery", "Searching For Combination", find);
      if ($combinations.includes(find)) {
        var $result = svi.imgs.map(function(x) {
          return { id: parseInt(x), k: parseInt(i) };
        });
        this.sviLog("Gallery", "Found Images For Combination", $result);
        return $result;
      }
    });
  };
  Vue.prototype.RetrieveAttributesCombinations = function($attributes, $isthumbGal) {
    let $result = [];
    this.sviLog("Gallery", "Attributes Processed", { attributes: $attributes, count: Object.keys($attributes).length });
    if ("triger_match" in wcsvi.options && parseInt(wcsvi.options.triger_match) && $attributes.length > 1) {
      var combo = $attributes.join("_svipro_");
      $result.push(combo);
    } else {
      var f = function(prefix, $attributes2) {
        for (var i = 0; i < $attributes2.length; i++) {
          if (prefix) {
            $result.push(prefix + "_svipro_" + $attributes2[i]);
            f(prefix + "_svipro_" + $attributes2[i], $attributes2.slice(i + 1));
          } else {
            $result.push(prefix + $attributes2[i]);
            f(prefix + $attributes2[i], $attributes2.slice(i + 1));
          }
        }
      };
      f("", $attributes);
    }
    if ("keep_thumbnails" in wcsvi.options && parseInt(wcsvi.options.keep_thumbnails))
      this.originalAttributes = this.RetrieveImagesAccordingToSelectedOriginalAttributes(this.removeDuplicates($result));
    if ($result.length < 1) {
      $result.unshift("svidefault");
    } else {
      $result.push("sviproglobal");
    }
    if ("keep_thumbnails_option" in wcsvi.options && wcsvi.options.keep_thumbnails_option == "svidefault")
      $result.unshift("svidefault");
    if ((!("swselect" in wcsvi.options) || !parseInt(wcsvi.options.swselect)) && this.attributes.chosenCount < this.attributes.count || parseInt(wcsvi.options.keep_thumbnails)) {
      $result = $jq.map($result, ($attribute) => {
        if (wcsvi.options.keep_thumbnails_option == "svidefault" && $attribute == "svidefault")
          return $attribute;
      });
    } else if ("swselect" in wcsvi.options && parseInt(wcsvi.options.swselect) && this.attributes.chosenCount > 0 || this.attributes.chosenCount > 0) {
      $result = $jq.map($result, ($attribute) => {
        if ($attribute != "svidefault")
          return $attribute;
      });
    }
    if ("variation_thumbnails" in wcsvi.options && this.boolEval(wcsvi.options.variation_thumbnails)) {
      if (!$isthumbGal) {
        $result = ["svidefault"];
      } else {
        $result = $jq.map($result, ($attribute) => {
          if ($attribute != "svidefault")
            return $attribute;
        });
      }
    }
    return this.removeDuplicates($result);
  };
  Vue.prototype.removeDuplicates = function(array) {
    return array.filter((a, b) => array.indexOf(a) === b);
  };
  Vue.prototype.removeDuplicatesID = function(array) {
    const uniqueArray = array.filter((thing, index2) => {
      return index2 === array.findIndex((obj) => {
        return obj.id === thing.id;
      });
    });
    return uniqueArray;
  };
  Vue.prototype.removeDuplicatesByKeyID = function(array) {
    const uniqueArray = array.filter((thing, index2) => {
      const _thing = JSON.stringify(thing.id);
      if ("video" in wcsvi.options && parseInt(wcsvi.options.video)) {
        if (thing.video)
          return true;
      }
      return index2 === array.findIndex((obj) => {
        return JSON.stringify(obj.id) === _thing;
      });
    });
    return uniqueArray;
  };
  Vue.prototype.convertAttributesNames = function($attribute) {
    let $string = "tetste";
    if ($attribute.match(/%[0-9a-f]{2}/i)) {
      this.consoleLog("MATCH");
      $string = $attribute;
    } else {
      $string = this.slugify($attribute);
      this.consoleLog("SLUGIFY", [$string, $string.length]);
    }
    return $string;
  };
  Vue.prototype.slugify = function($attribute) {
    let object = window.wcsvislugs;
    let $result = Object.keys(object).find((key) => this.escapeHtml(object[key]) === $attribute.trim());
    return $result ? $result : $attribute;
  };
  Vue.prototype.isEncoded = function(str) {
    return typeof str == "string" && decodeURIComponent(str) !== str;
  };
  Vue.prototype.boolEval = function(opt) {
    return parseInt(opt) || 0;
  };
  Vue.prototype.escapeHtml = function(string) {
    return string.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, '"').replace(/&#039;/g, "'");
  };
  Vue.prototype.sliderMain = function() {
    let sliderOptions = {
      effect: "slider_effect" in wcsvi.options && wcsvi.options.slider_effect ? wcsvi.options.slider_effect : "slide",
      spaceBetween: 10,
      loopedSlides: 1,
      autoHeight: true,
      updateOnImagesReady: true,
      preloadImages: "slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload) ? false : true,
      lazy: "slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload) ? {
        loadPrevNext: true,
        loadOnTransitionStart: true
      } : false,
      on: {
        click: (swiper, event) => {
          this.photo(this.slides[this.galleryTop.realIndex], event, "slider");
        },
        slideChange: () => {
          const realIndex = this.galleryTop.realIndex;
          if (this.galleryThumbs && typeof this.galleryThumbs.slideTo === "function") {
            this.galleryThumbs.slideTo(realIndex);
          }
        },
        slideChangeTransitionStart: () => {
          this.stopVideos();
          this.plyr(false);
          if (this.mainTitle) this.runInsertTitle(this.galleryTop.realIndex);
          this.vidRun(this.galleryTop.realIndex, this.slides);
          this.$forceUpdate();
        }
      }
    };
    if ("slider_autoslide" in wcsvi.options && this.boolEval(wcsvi.options.slider_autoslide)) {
      sliderOptions.autoplay = {
        delay: wcsvi.options.slider_autoslide_ms || 5e3,
        disableOnInteraction: this.boolEval(wcsvi.options.slider_autoslide_disableOnInteraction),
        pauseOnMouseEnter: true
      };
    }
    if (!("disable_thumb" in wcsvi.options) || !this.boolEval(wcsvi.options.disable_thumb)) {
      sliderOptions.thumbs = {
        swiper: this.galleryThumbs
        // Official Swiper 11 way to sync with thumbnails
      };
    }
    if ("slider_navigation" in wcsvi.options && this.boolEval(wcsvi.options.slider_navigation)) {
      sliderOptions.navigation = {
        nextEl: ".swiperTop-swiper-button-next",
        prevEl: ".swiperTop-swiper-button-prev"
      };
    }
    if ("slider_pagination" in wcsvi.options && this.boolEval(wcsvi.options.slider_pagination)) {
      sliderOptions.pagination = {
        el: ".sviswiper-pagination",
        dynamicBullets: "slider_paginationDynamicBullets" in wcsvi.options && this.boolEval(wcsvi.options.slider_paginationDynamicBullets),
        type: "slider_paginationType" in wcsvi.options && wcsvi.options.slider_paginationType ? wcsvi.options.slider_paginationType : "bullets",
        clickable: "slider_paginationclickable" in wcsvi.options && this.boolEval(wcsvi.options.slider_paginationclickable)
      };
    }
    return new Swiper(".svi-gallery-top", sliderOptions);
  };
  Vue.prototype.sliderThumbs = function() {
    let sliderOptions = {
      spaceBetween: 10,
      direction: "slider_position" in wcsvi.options ? parseInt(wcsvi.options.slider_position) === 0 ? "horizontal" : "vertical" : "horizontal",
      slidesPerView: "slider_position" in wcsvi.options ? parseInt(wcsvi.options.slider_position) === 0 ? parseFloat(wcsvi.options.columns) : 4.8 : parseInt(wcsvi.options.columns),
      loopedSlides: 1,
      watchSlidesVisibility: true,
      watchSlidesProgress: true,
      centeredSlides: false,
      // Set to false for a left-aligned behavior
      slideToClickedSlide: true,
      // Allows clicking on a thumbnail to center it in the main swiper
      preloadImages: "slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload) ? false : true,
      lazy: "slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload) ? {
        loadPrevNext: true
      } : false,
      watchOverflow: true,
      threshold: 2,
      on: {
        click: (swiper) => {
          this.runOpacity(swiper);
          if ("variation_swap" in wcsvi.options && this.boolEval(wcsvi.options.variation_swap)) {
            let $item = this.slides[this.galleryTop.realIndex];
            this.ThumbClickSwap($item);
          }
        }
      }
    };
    if ("slider_navigation_thumb" in wcsvi.options && this.boolEval(wcsvi.options.slider_navigation_thumb)) {
      sliderOptions.navigation = {
        nextEl: ".swiperThumbs-swiper-button-next",
        prevEl: ".swiperThumbs-swiper-button-prev"
      };
    }
    return new Swiper(".svi-gallery-thumbs", sliderOptions);
  };
  Vue.prototype.isEquivalent = function(aOriginal, bOriginal) {
    let a = JSON.parse(JSON.stringify(aOriginal)).map(function(i3, obj) {
      return i3["id"];
    });
    let b = JSON.parse(JSON.stringify(bOriginal)).map(function(i3, obj) {
      return i3["id"];
    });
    if ("skip_equivalent" in wcsvi.options && this.boolEval(wcsvi.options.skip_equivalent) && "slider" in wcsvi.options && !this.boolEval(wcsvi.options.slider))
      return false;
    if (JSON.stringify(a) === JSON.stringify(b))
      return true;
    else
      return false;
  };
  Vue.prototype.imgEl = function($attr, $type = "static") {
    if (typeof $attr != "object")
      return "";
    if (!("video" in wcsvi.options) || !parseInt(wcsvi.options.video)) {
      $attr.video = "";
    }
    let $imgEl;
    let $video = "";
    let $lightbox = "";
    let $theBuild = "";
    let $activateTitle = "";
    let $runActiveTitle = false;
    if ($attr.video != "")
      $video = '<div class="sviicon-play"><span class="dashicons dashicons-controls-play"></span></div>';
    if ("lightbox" in wcsvi.options && parseInt(wcsvi.options.lightbox) && ("lightbox_icon" in wcsvi.options && parseInt(wcsvi.options.lightbox_icon))) {
      $lightbox = '<span style="color:' + wcsvi.options.lightbox_iconcolor + '" class="sviLigthgallery"></span>';
    }
    switch ($type) {
      case "static":
        if ($attr.video != "") {
          $theBuild = this.plyr_video($attr);
          $video = "";
        } else
          $theBuild = this.imgElstatic($attr);
        $imgEl = $lightbox + $video + $theBuild;
        $runActiveTitle = "main";
        break;
      case "slider":
        if ($attr.video != "") {
          $theBuild = this.plyr_video($attr);
          $video = "";
        } else
          $theBuild = this.imgElslider($attr);
        $imgEl = $lightbox + $video + $theBuild;
        break;
      case "sliderthumb":
        $theBuild = this.imgElslider($attr, true);
        $imgEl = $video + $theBuild;
        break;
      case "stack":
        if ($attr.video != "") {
          $theBuild = this.plyr_video($attr);
          $video = "";
        } else {
          $theBuild = this.imgElstack($attr);
        }
        $imgEl = $lightbox + $video + $theBuild;
        $runActiveTitle = "main";
        break;
      case "svimainthumb":
        $theBuild = this.imgElstatic($attr, true);
        $imgEl = $video + $theBuild;
        $runActiveTitle = "thumb";
        break;
      case "svivariationsthumb":
        if (!$video) {
          if (false in wcsvi.options || !this.boolEval(wcsvi.options.variation_thumbnails_lb) || "variation_thumbnails_lb" in wcsvi.options && this.boolEval(wcsvi.options.variation_thumbnails_lb) && (false in wcsvi.options || !parseInt(wcsvi.options.lightbox_icon)))
            $lightbox = "";
        } else {
          if (false in wcsvi.options || !parseInt(wcsvi.options.lightbox_icon))
            $lightbox = "";
        }
        $theBuild = this.imgElstatic($attr, true);
        $imgEl = $lightbox + $video + $theBuild;
        $runActiveTitle = "thumb";
        break;
    }
    if ("activateTitle" in this && this.activateTitle && $runActiveTitle != false) {
      if ($runActiveTitle == "thumb")
        $activateTitle = "thumb_imagecaption_option" in wcsvi.options ? this.returnTitle(wcsvi.options.thumb_imagecaption_option, $attr) : "";
      if ($runActiveTitle == "main")
        $activateTitle = "imagecaption_option" in wcsvi.options ? this.returnTitle(wcsvi.options.imagecaption_option, $attr) : "";
      $imgEl = $imgEl + '<div class="svititleimg">' + $activateTitle + "</div>";
    }
    return $imgEl;
  };
  const checkAttrAndOption = (attr, optionKey, options) => optionKey in options && Number(options[optionKey]) && attr;
  Vue.prototype.imgElstatic = function($attr, $thumb = false, $outerHTML = true) {
    const {
      src,
      title,
      alt,
      id,
      srcset,
      height,
      width,
      class: classAttr,
      // 'class' is a reserved keyword
      thumb_class,
      large_image_height,
      thumb_image_height,
      large_image_width,
      thumb_image_width,
      large_image,
      thumb_image
    } = $attr;
    const { options } = wcsvi;
    const img = document.createElement("img");
    if (src) img.src = !$thumb ? large_image : thumb_image;
    if (checkAttrAndOption(title, "svititleattr", options)) img.title = this.decodeEntities(title);
    if (checkAttrAndOption(alt, "svialtattr", options)) img.alt = this.decodeEntities(alt);
    if (id) img.dataset.sviid = id;
    if (checkAttrAndOption(srcset, "sviesrcset", options)) img.dataset.srcset = srcset;
    if ("lens" in options && Number(options.lens) && !$thumb) img.dataset.zoomImage = src;
    if (height) img.height = !$thumb ? large_image_height : thumb_image_height;
    if (width) img.width = !$thumb ? large_image_width : thumb_image_width;
    if (classAttr && $outerHTML) img.className = !$thumb ? classAttr : `${thumb_class} svi-thumb-image`;
    return $outerHTML ? img.outerHTML : img;
  };
  Vue.prototype.decodeEntities = function(encodedString) {
    const textArea = document.createElement("textarea");
    textArea.innerHTML = encodedString;
    return textArea.value;
  };
  Vue.prototype.imgElslider = function($attr, $thumb = false) {
    let img = this.imgElstatic($attr, $thumb, false);
    if ("class" in $attr)
      img.className = !$thumb ? $attr.class : $attr.thumb_class + " svi-thumb-image";
    if ("slider_lazyload" in wcsvi.options && this.boolEval(wcsvi.options.slider_lazyload)) {
      img.dataset.src = !$thumb ? $attr.large_image : $attr.thumb_image;
      img.className += " swiper-lazy ";
    }
    img.className = img.className.trim();
    return img.outerHTML;
  };
  Vue.prototype.imgElstack = function($attr) {
    let img = this.imgElstatic($attr, false, false);
    img.className += "svi-stack-image ";
    img.className = img.className.trim();
    return img.outerHTML;
  };
  Vue.prototype.runOpacity = function($item) {
    if (!("thumbnails_showactive" in wcsvi.options) || !parseInt(wcsvi.options.thumbnails_showactive))
      return;
    if (typeof $item == "undefined") {
      if ("slider" in wcsvi.options && this.boolEval(wcsvi.options.slider))
        $jq(this.$el).find(".svi-gallery-thumbs .swiper-slide-active img").addClass("svi-opacity");
      else
        $jq(this.$el).find("ul>li:first-child img").addClass("svi-opacity");
    } else {
      $jq("img.svi-opacity").removeClass("svi-opacity");
      if ("slider" in wcsvi.options && this.boolEval(wcsvi.options.slider))
        $jq(this.$el).find(".svi-gallery-thumbs .swiper-slide-thumb-active img").addClass("svi-opacity");
      else
        $jq($item.target).addClass("svi-opacity");
    }
  };
  Vue.prototype.placeholder = function() {
    return {
      alt: "",
      caption: "",
      class: wcsvi.options.placeholder_img.class,
      full_image: wcsvi.options.placeholder_img.src,
      full_image_height: wcsvi.options.placeholder_img.height,
      full_image_width: wcsvi.options.placeholder_img.width,
      height: wcsvi.options.placeholder_img.height,
      id: "xPlaceholder",
      large_image: wcsvi.options.placeholder_img.src,
      large_image_height: wcsvi.options.placeholder_img.height,
      large_image_width: wcsvi.options.placeholder_img.width,
      product_img: true,
      sizes: wcsvi.options.placeholder_img.sizes,
      src: wcsvi.options.placeholder_img.src,
      srcset: wcsvi.options.placeholder_img.srcset,
      thumb_image: wcsvi.options.placeholder_img.src,
      thumb_image_height: wcsvi.options.placeholder_img.height,
      thumb_image_width: wcsvi.options.placeholder_img.width,
      title: "",
      width: wcsvi.options.placeholder_img.width,
      video: ""
    };
  };
  Vue.prototype.getWindowWidth = function($return = false) {
    if (document.documentElement.clientWidth < 768) {
      if ($return)
        return "mobile";
      else
        this.windowType = "mobile";
    }
    if (document.documentElement.clientWidth >= 768) {
      if ($return)
        return "desktop";
      else
        this.windowType = "desktop";
    }
  };
  Vue.prototype.runInsertTitle = function($index) {
    let $img = this.slides[$index];
    this.imgTitle = "imagecaption_option" in wcsvi.options ? this.returnTitle(wcsvi.options.imagecaption_option, $img) : "";
  };
  Vue.prototype.returnTitle = function($option, $img) {
    return $option == "caption" ? $img.caption : $img.title;
  };
  Vue.prototype.ThumbClickSwap = function($item) {
    let attributeFields = this.$root.$children[0].attributeFields;
    let $slugs = this.RetrieveTheSlugs($item.id);
    let _this = this;
    attributeFields.map(function(i3, select) {
      let $found = false;
      let isPopulated = $jq(select).val() === "" ? false : true;
      $jq.each($slugs[0], function(i2, v2) {
        $jq(select).find("option").map(function(selIndex, option) {
          if (_this.slugify(option.value) == v2 && !$jq(option).is(":selected")) {
            $jq(select).val(option.value).trigger("change");
            $found = true;
            $jq(".swatches-support").trigger("click");
          }
          if (_this.slugify(option.value) == v2) {
            $found = true;
          }
        });
      });
      if (!$found) {
        if (!isPopulated)
          $jq(select).prop("selectedIndex", 0);
      }
    });
  };
  Vue.prototype.RetrieveTheSlugs = function($imgID) {
    let sviGal = this.$root.$children[0].gallery.svi;
    return $jq.map(sviGal, (svi) => {
      if (svi.imgs.includes($imgID.toString())) {
        return [svi.slugs];
      }
    });
  };
  Vue.prototype.stopVideos = function() {
    document.querySelectorAll("video").forEach((video) => {
      video.pause();
      video.currentTime = 0;
    });
    document.querySelectorAll(".plyr__video-wrapper.plyr__video-embed iframe").forEach((iframe) => {
      iframe.src = iframe.src;
    });
  };
  Vue.prototype.photo = function($item, $event, $type) {
    if ($type == "variationThumbImages" && (!("lightbox" in wcsvi.options) || !this.boolEval(wcsvi.options.lightbox) || $event.target.localName != "span" && $event.target.localName != "img")) {
      if ($jq(this.$el).find("video,.plyr").length > 0) {
        $jq(this.$el).find("video,.plyr").remove();
        $jq(this.$el).find(".sviicon-play").show();
      }
      $jq($event.target).closest("li").find(".sviicon-play").hide();
      $jq($event.target).closest(".svivariationsthumb").append(this.plyr_video($item));
      this.plyr($item);
      return;
    }
    if (!("lightbox" in wcsvi.options) || !this.boolEval(wcsvi.options.lightbox) || $event.target.localName != "span" && $event.target.localName != "img" && ($event.target.className != "zoomLens" && $event.target.className != "zoomWindow"))
      return;
    if ($event.target.className.includes("play") && $type == "stack") {
      $jq($event.target).closest("li").find("img,.sviicon-play").hide();
      $jq($event.target).closest("li").append(this.plyr_video($item));
      this.plyr($item);
    } else {
      if (!("lightbox_iconclick" in wcsvi.options) || !this.boolEval(wcsvi.options.lightbox_iconclick)) {
        this.photoSwipeTrigger($item, $type);
      } else if ("lightbox_iconclick" in wcsvi.options && this.boolEval(wcsvi.options.lightbox_iconclick) && $event.target.className == "sviLigthgallery") {
        this.photoSwipeTrigger($item, $type);
      }
    }
  };
  Vue.prototype.photoSwipeTrigger = function($item, $type) {
    if ($type == "slider")
      this.$photoswipe.open($item, this.slides, this.$photoswipe, this.$root.$children[0].pid);
    else if ($type == "static")
      this.$photoswipe.open($item, this.gallery, this.$photoswipe, this.$root.$children[0].pid);
    else if ($type == "stack" || $type == "variationThumbImages")
      this.$photoswipe.open($item, this.list, this.$photoswipe, this.$root.$children[0].pid);
  };
  Vue.prototype.findIndex = function($item, $gallery) {
    let $index = 0;
    $jq.each($gallery, (i, v2) => {
      if ($item.id == v2.id) {
        $index = i;
        return false;
      }
    });
    return $index;
  };
  Vue.prototype.sviLog = function(namespace, action, data = null, level = "debug") {
    const isProd = this.boolEval(wcsvi.prod);
    const forceDebug = typeof window !== "undefined" && window.location.search.includes("svi_debug");
    if (isProd && !forceDebug) return;
    if (typeof console === "undefined") return;
    const time = performance.now().toFixed(2);
    const prefix = `%c[SVI:${namespace}]%c [${time}ms] %c${action}`;
    let color = "#3498db";
    if (namespace === "Plyr") color = "#e74c3c";
    if (namespace === "Thumbnails") color = "#2ecc71";
    if (namespace === "Legacy") color = "#95a5a6";
    if (namespace === "Lightbox") color = "#9b59b6";
    const s1 = `color: white; background: ${color}; font-weight: bold; padding: 2px 4px; border-radius: 3px;`;
    const s2 = `color: #7f8c8d; font-size: 0.9em; margin-left: 4px;`;
    const s3 = `color: ${color}; font-weight: bold; margin-left: 4px;`;
    if (data !== null) {
      if (level === "warn" || level === "error") {
        console[level](prefix, s1, s2, s3, data);
      } else {
        console.log(prefix, s1, s2, s3, data);
      }
    } else {
      console.log(prefix, s1, s2, s3);
    }
  };
  Vue.prototype.consoleLog = function(...args) {
    this.sviLog("Legacy", args[0] || "Log", args.length > 1 ? args.slice(1) : null);
  };
  Vue.prototype.plyr_video = function($img) {
    if (!$img.video)
      return;
    let data = this.parseVideo($img.video);
    let poster = "video_poster" in wcsvi.options && parseInt(wcsvi.options.video_poster) ? $img.large_image : "";
    let UIcolor = "";
    if ("video_maincolor" in wcsvi.options && wcsvi.options.video_maincolor)
      UIcolor = 'style="--plyr-color-main: ' + wcsvi.options.video_maincolor + ';"';
    const isTrue2 = (val) => val === 1 || val === "1" || val === "yes" || val === "on" || val === true;
    const shouldAutoplay = isTrue2(wcsvi.options.video_autoplay);
    const shouldMute = isTrue2(wcsvi.options.video_muted);
    let nativeAttrs = ' playsinline="playsinline" ';
    if (shouldAutoplay) {
      nativeAttrs += ' autoplay="autoplay" muted="muted" ';
    } else if (shouldMute) {
      nativeAttrs += ' muted="muted" ';
    }
    if (isTrue2(wcsvi.options.video_loop ?? 0)) {
      nativeAttrs += ' loop="loop" ';
    }
    this.consoleLog("DEBUG PLYR_VIDEO HTML GENERATION", { wcsviAutoplay: wcsvi.options.video_autoplay, shouldAutoplay, nativeAttrs, isLightbox: this.$el.classList.contains("pswp") });
    if (data.type == "youtube" || data.type == "vimeo")
      return '<div class="svi-player_' + $img.id + "_" + $img.idk + "_" + this.$el.className + '" data-plyr-provider="' + data.type + '" data-plyr-embed-id="' + data.id + '" data-poster="' + poster + '" ' + UIcolor + "></div>";
    else {
      return "<video " + nativeAttrs + ' class="svi-player_' + $img.id + "_" + $img.idk + "_" + this.$el.className + '" data-poster="' + poster + '" ' + UIcolor + ' preload="auto"><source src="' + $img.video + '" type="video/mp4" /></video>';
    }
  };
  Vue.prototype.plyr = function($data, $pid = false) {
    var _a, _b;
    let player = false;
    if ((_a = wcsvi.options) == null ? void 0 : _a.video) {
      if (!$data || $data.video === "") {
        this.$bus.$emit("vidPlayer_" + $pid, player);
        return;
      }
      let _this = this;
      let $controls = typeof wcsvi.options.video_controls === "object" ? wcsvi.options.video_controls : false;
      let $el = this.$el.className.split(" ");
      let $class = `.svi-player_${$data.id}_${$data.idk}_${$el[0]}`;
      const isSviproslider = _this.$el.className === "sviproslider";
      const pswp = _this.$el.classList.contains("pswp");
      const isTrue2 = (val) => val === 1 || val === "1" || val === "yes" || val === "on" || val === true;
      const shouldAutoplay = isTrue2(wcsvi.options.video_autoplay);
      const shouldMute = isTrue2(wcsvi.options.video_muted);
      this.consoleLog("DEBUG PLYR INITIALIZATION", { wcsviAutoplay: wcsvi.options.video_autoplay, shouldAutoplay, pswp, class: $class });
      const videoElements = document.querySelectorAll($class);
      videoElements.forEach((el) => {
        if (el.tagName === "VIDEO") {
          if (shouldAutoplay || shouldMute) {
            el.muted = true;
            el.defaultMuted = true;
          }
        }
      });
      player = new Plyr($class, {
        autoplay: shouldAutoplay,
        muted: shouldAutoplay ? true : shouldMute,
        clickToPlay: isTrue2(wcsvi.options.video_clickToPlay ?? 1),
        hideControls: isTrue2(wcsvi.options.video_hideControls ?? 1),
        fullscreen: { enabled: isTrue2(wcsvi.options.video_fullscreen ?? 1) },
        controls: $controls,
        ratio: wcsvi.options.video_ratio === "auto" || pswp ? "" : wcsvi.options.video_ratio ?? "",
        loop: { active: isTrue2(wcsvi.options.video_loop ?? 0) },
        storage: { enabled: false }
      });
      this.$bus.$emit("vidPlayer_" + $pid, player);
      let ratio = (_b = wcsvi.options) == null ? void 0 : _b.video_ratio;
      if (ratio && ratio !== "auto" && ratio.includes(":") && !pswp) {
        const [width, height] = ratio.split(":").map(Number);
        const aspectRatio = width / height;
        const plyrElements = _this.$el.querySelectorAll(".plyr");
        plyrElements.forEach((plyrEl) => {
          plyrEl.style.aspectRatio = `${aspectRatio}`;
        });
        if (isSviproslider) {
          _this.galleryTop.update();
        }
      }
    }
  };
  Vue.prototype.vidRun = function(curIndex = 0, $gallery, $pid = false) {
    if ($gallery[curIndex].video) {
      this.plyr($gallery[curIndex], $pid);
    } else {
      this.plyr(false, $pid);
    }
  };
  Vue.prototype.parseVideo = function($url) {
    let type = "html5";
    if ($url.match(/(http:|https:|)\/\/(player.|www.)?(vimeo\.com|youtu(be\.com|\.be|be\.googleapis\.com))\/(video\/|embed\/|watch\?v=|v\/)?([A-Za-z0-9._%-]*)(\&\S+)?/)) {
      if (RegExp.$3.indexOf("youtu") > -1) {
        type = "youtube";
      } else if (RegExp.$3.indexOf("vimeo") > -1) {
        type = "vimeo";
      }
    } else {
      type = "html5";
    }
    return {
      type,
      id: RegExp.$6
    };
  };
  Vue.prototype.colposition = function(index2) {
    var skey = Number(index2);
    var $classes = [""];
    if (skey === 0 || skey % this.columns === 0) {
      $classes.push("first");
    }
    if ((skey + 1) % this.columns === 0) {
      $classes.push("last");
    }
    if (index2 == 0 && this.thumbtype == "svimainthumb") this.runOpacity();
    return $classes.join(" ");
  };
  Vue.prototype.calculateAspectRatioFit = function($img = false) {
    let maxWidth = $jq(this.$el).outerWidth();
    let srcWidth, srcHeight, maxHeight;
    if ($img && $img.width && $img.height && $img.full_image_height) {
      srcWidth = $img.width;
      srcHeight = $img.height;
      maxHeight = $img.full_image_height;
    } else if (this.image && this.image.width && this.image.height && this.image.full_image_height) {
      srcWidth = this.image.width;
      srcHeight = this.image.height;
      maxHeight = this.image.full_image_height;
    } else {
      return { width: 0, height: 0 };
    }
    let ratio = Math.min(maxWidth / srcWidth, maxHeight / srcHeight);
    return { width: srcWidth * ratio, height: srcHeight * ratio };
  };
  Vue.prototype.runKeepThumbnailsImageChange = function($imgs, $isSlider) {
    let _this = this;
    let list = false;
    if ($isSlider)
      list = this.slides;
    else
      list = this.list;
    if (!list || $imgs.length < 1)
      return;
    $jq.each(list, (i, v2) => {
      $jq.each($imgs, (i2, v22) => {
        if ("id" in v22 && v2.id == v22.id) {
          if ($isSlider)
            _this.galleryTop.slideTo(i);
          else
            $jq(_this.$el).find("[data-sviid='" + v2.id + "']").click();
        }
      });
    });
  };
  Vue.prototype.waitForEl = function(selector, callback, maxtries = false, interval = 100, dataObj = false) {
    const poller = setInterval(() => {
      const el = jQuery(selector);
      const retry = maxtries === false || maxtries-- > 0;
      if (retry && el.length < 1) return;
      clearInterval(poller);
      if (dataObj) {
        dataObj.form = $jq(".variations_form[data-product_id='" + dataObj.product_id + "']");
        callback(dataObj);
      } else
        callback(el || null);
    }, interval);
  };
  Vue.prototype.buildSwatches = function() {
    let $form = this.form ? this.form : $jq("div.wcsvfs-swatches").first().closest("form");
    $form.on("click", ".swatch", function(e) {
      e.preventDefault();
      var $el = $jq(this), $select = $el.closest(".value").find("select"), value = $el.attr("data-value");
      if ($el.hasClass("disabled")) {
        return;
      }
      $select.trigger("focusin");
      if (!$select.find('option[value="' + value + '"]').length) {
        $el.siblings(".swatch").removeClass("selected");
        $select.val("").change();
        $form.trigger("wcsvfs_no_matching_variations", [$el]);
        return;
      }
      if ($el.hasClass("selected")) {
        $select.val("");
        $el.removeClass("selected");
      } else {
        $el.addClass("selected").siblings(".selected").removeClass("selected");
        $select.val(value);
      }
      $select.change();
    }).on("click", ".reset_variations", function() {
      $form.find(".swatch.selected").removeClass("selected");
      $form.find(".swatch.disabled").removeClass("disabled");
    }).on("woocommerce_update_variation_values", function() {
      setTimeout(function() {
        $form.find("tbody tr").each(function() {
          var $variationRow = $jq(this), $options = $variationRow.find("select").find("option"), $selected = $options.filter(":selected"), values = [];
          $options.each(function(index2, option) {
            if (option.value !== "") {
              values.push(option.value);
            }
          });
          $variationRow.find(".swatch").each(function() {
            var $swatch = $jq(this), value = $swatch.attr("data-value");
            if (values.indexOf(value) > -1) {
              $swatch.removeClass("disabled");
              if ($selected.length && value === $selected.val()) {
                $swatch.addClass("selected");
              }
            } else {
              $swatch.addClass("disabled");
              if ($selected.length && value === $selected.val()) {
                $swatch.removeClass("selected");
              }
            }
          });
        });
      }, 150);
    }).on("wcsvfs_no_matching_variations", function() {
      window.alert(wc_add_to_cart_variation_params.i18n_no_matching_variations_text);
    });
  };
  var lensoptions = {
    zoomContainerAppendTo: ".woosvi_strap",
    container: "sviZoomContainer",
    cursor: "pointer",
    attrImageZoomSrc: "zoom-image",
    galleryActiveClass: "active",
    containLensZoom: true,
    borderColour: "lens_border" in wcsvi.options && wcsvi.options.lens_border ? wcsvi.options.lens_border : "#888",
    lensBorderSize: "lens_lensBorder" in wcsvi.options && wcsvi.options.lens_lensBorder ? wcsvi.options.lens_lensBorder : 1,
    lensShape: "lens_type" in wcsvi.options && wcsvi.options.lens_type ? wcsvi.options.lens_type : "round",
    lensSize: "lens_size" in wcsvi.options && wcsvi.options.lens_size ? wcsvi.options.lens_size : 125,
    zoomType: "lens_zoomtype" in wcsvi.options && wcsvi.options.lens_zoomtype ? wcsvi.options.lens_zoomtype : "lens",
    easing: "lens_easing" in wcsvi.options && parseInt(wcsvi.options.lens_easing) ? true : false,
    scrollZoom: "lens_scrollzoom" in wcsvi.options && wcsvi.options.lens_scrollzoom ? true : false,
    lensFadeIn: "lens_lensFadeIn" in wcsvi.options && parseInt(wcsvi.options.lens_lensFadeIn) ? wcsvi.options.lens_lensFadeIn : false,
    zoomWindowFadeIn: "lens_zoomWindowFadeIn" in wcsvi.options && parseInt(wcsvi.options.lens_zoomWindowFadeIn) ? wcsvi.options.lens_zoomWindowFadeIn : false,
    zIndex: "lens_zIndex" in wcsvi.options && wcsvi.options.lens_zIndex ? wcsvi.options.lens_zIndex : 1e3,
    zoomWindowWidth: "lens_zoomWindowWidth" in wcsvi.options && parseInt(wcsvi.options.lens_zoomWindowWidth) ? wcsvi.options.lens_zoomWindowWidth : 400,
    zoomWindowHeight: "lens_zoomWindowHeight" in wcsvi.options && parseInt(wcsvi.options.lens_zoomWindowHeight) ? wcsvi.options.lens_zoomWindowHeight : 400,
    zoomWindowPosition: "rtl" in wcsvi.options && parseInt(wcsvi.options.rtl) ? 11 : 1
  };
  Vue.directive("ezPlus", {
    update: function(el, binding, vnode) {
      let Mobile = false;
      if (!parseInt(wcsvi.options.lens))
        return;
      if (document.documentElement.clientWidth < 768) {
        Mobile = true;
      }
      if (Mobile && !parseInt(wcsvi.options.lens_mobiledisabled))
        return;
      let img = $jq(el).find("img");
      $jq("div.sviZoomContainer").remove();
      $jq(img).ezPlus(lensoptions);
    }
  });
  Vue.directive("ezPlusStacked", {
    bind: function(el, binding, vnode) {
      let Mobile = false;
      if (!parseInt(wcsvi.options.lens))
        return;
      if (document.documentElement.clientWidth < 768) {
        Mobile = true;
      }
      if (Mobile && (false in wcsvi.options || !parseInt(wcsvi.options.lens_mobiledisabled)))
        return;
      if ($jq(el).find(".sviicon-play").length > 0)
        return;
      let img = $jq(el).find("img");
      let stacklensoptions = lensoptions;
      stacklensoptions.container = "sviStackedZoomContainer";
      var handle = setInterval(() => {
        $jq(img).mouseover(function() {
          $jq(img).ezPlus(stacklensoptions);
        });
        clearInterval(handle);
      }, 15);
    }
  });
  Vue.directive("ezPlusSlider", {
    componentUpdated: function(el, binding, vnode) {
      let Mobile = false;
      if (!parseInt(wcsvi.options.lens))
        return;
      if (document.documentElement.clientWidth < 768) {
        Mobile = true;
      }
      if (Mobile && (false in wcsvi.options || !parseInt(wcsvi.options.lens_mobiledisabled)))
        return;
      let $img = $jq(el).find(".swiper-slide-active img");
      lensoptions.zoomContainerAppendTo = ".woosvi_strap .svi-gallery-top .swiper-slide-active";
      lensoptions.zIndex = 9;
      $jq("div.zoomContainer,div.zoomWindowContainer").remove();
      $jq($img).ezPlus(lensoptions);
      if (lensoptions.zoomType == "window") {
        var refreshId = setInterval(function() {
          if ($jq("div.sviZoomContainer div.zoomWindowContainer").length > 0) {
            $jq("div.sviZoomContainer div.zoomWindowContainer").detach().appendTo(".woosvi_strap");
            clearInterval(refreshId);
          }
        }, 10);
      }
    }
  });
  Vue.directive("playVideo", {
    bind: function(el, binding, vnode) {
      setTimeout(() => {
        vnode.context.plyr(binding.value);
      }, 500);
    }
  });
  const _sfc_main = {
    name: "pswp",
    data: function() {
      return {
        options: {
          closeEl: parseInt(wcsvi.options.lightbox_close) ? true : false,
          captionEl: "lightbox_title" in wcsvi.options && parseInt(wcsvi.options.lightbox_title) ? true : false,
          fullscreenEl: parseInt(wcsvi.options.lightbox_fullScreen) ? true : false,
          zoomEl: parseInt(wcsvi.options.lightbox_zoom) ? true : false,
          shareEl: parseInt(wcsvi.options.lightbox_share) ? true : false,
          counterEl: parseInt(wcsvi.options.lightbox_counter) ? true : false,
          arrowEl: parseInt(wcsvi.options.lightbox_controls) ? true : false,
          preloaderEl: true,
          index: 0
        },
        thumbnails: "lightbox_thumbnails" in wcsvi.options && parseInt(wcsvi.options.lightbox_thumbnails) ? true : false,
        gallery: false,
        photoEl: false,
        photoswipe: false,
        toggleCheck: true,
        pid: 0
      };
    },
    mounted: function() {
      this.$nextTick(function() {
      });
    },
    watch: {
      photoswipe: function(val) {
        let _this = this;
        this.photoswipe.listen("afterChange", function() {
          let curIndex = val.getCurrentIndex();
          _this.vidRun(curIndex, _this.gallery, _this.pid);
        });
        this.photoswipe.listen("close", function() {
          let curIndex = val.getCurrentIndex();
          _this.plyr(false, _this.pid);
          _this.$bus.$emit("vidPlayerSlider_" + _this.pid, curIndex);
          _this.$bus.$emit("vidPlayerStack_" + _this.pid, curIndex);
        });
      }
    },
    methods: {
      open($item, $gallery, $photoEl, $pid) {
        this.pid = $pid;
        this.plyr(false, this.pid);
        this.gallery = $gallery;
        this.options.index = this.findIndex($item, this.gallery);
        this.photoEl = $photoEl;
        this.photoswipe = new PhotoSwipe(
          this.$el,
          //.pswp element
          PhotoSwipeUI_Default,
          // PhotoSwipe UI class
          this.lightbox_prep(),
          // Array with objects (slides).
          this.options
          //PhotoSwipe Options
        );
        if (this.thumbnails && !wcsvi.options.nothumbs) this.photoswipePreviews(this.photoswipe);
        this.photoswipe.init();
        setTimeout(() => {
          this.vidRun(this.options.index, this.gallery, this.pid);
        }, 100);
      },
      lightbox_prep() {
        var items = [];
        $jq.each(this.gallery, (i, v2) => {
          if ("video" in wcsvi.options && v2.video && parseInt(wcsvi.options.video)) {
            let vidPlayer = this.plyr_video(v2);
            items.push({
              html: vidPlayer,
              // optional alt attribute for thumbnail image
              msrc: v2.thumb_image
            });
          } else {
            items.push({
              src: v2.full_image,
              msrc: v2.thumb_image,
              w: v2.full_image_width,
              h: v2.full_image_height,
              title: v2.title
              // optional alt attribute for thumbnail image
            });
          }
        });
        return items;
      },
      photoswipePreviews(gallery) {
        let added = false;
        const props2 = {
          gallery,
          selector: "[data-previews]"
        };
        gallery.listen("gettingData", () => {
          if (added) return;
          added = true;
          this.addPreviews(props2);
        });
        gallery.listen("close", () => {
          this.removePreviews(props2);
        });
        gallery.listen("afterChange", () => {
          this.setActivePreview(props2);
        });
      },
      addPreviews({ gallery, selector }) {
        const { scrollWrap, items } = gallery;
        const place = scrollWrap.querySelector(selector);
        for (const item of items) {
          const { msrc: preview } = item;
          const element = document.createElement("img");
          element.setAttribute("src", preview);
          element.addEventListener("click", () => {
            gallery.goTo(items.indexOf(item));
          });
          place.appendChild(element);
        }
      },
      removePreviews({ gallery, selector }) {
        const { scrollWrap } = gallery;
        const place = scrollWrap.querySelector(selector);
        place.innerHTML = "";
      },
      setActivePreview({ gallery, selector }) {
        const { scrollWrap, currItem } = gallery;
        const { msrc: preview } = currItem;
        const place = scrollWrap.querySelector(selector);
        const previewElements = place.querySelectorAll("img");
        for (const element of previewElements) {
          const src = element.getAttribute("src");
          const className = "is-active";
          if (src === preview) {
            element.classList.add(className);
            element.scrollIntoView({ behavior: "smooth" });
          } else element.classList.remove(className);
        }
      },
      togglePreview() {
        this.toggleCheck = !this.toggleCheck;
        if (this.toggleCheck == true) {
          $jq(".pswp__previews").slideDown();
        } else {
          $jq(".pswp__previews").slideUp();
        }
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "pswp", attrs: { "tabindex": "-1", "role": "dialog", "aria-hidden": "true" } }, [_c("div", { staticClass: "pswp__bg" }), _c("div", { staticClass: "pswp__scroll-wrap" }, [_vm._m(0), _c("div", { staticClass: "pswp__ui pswp__ui--hidden" }, [_c("div", { staticClass: "pswp__top-bar" }, [_c("div", { staticClass: "pswp__counter" }), _c("button", { staticClass: "pswp__button pswp__button--close", attrs: { "title": "Close (Esc)" } }), _c("button", { staticClass: "pswp__button pswp__button--share", attrs: { "title": "Share" } }), _c("button", { staticClass: "pswp__button pswp__button--fs", attrs: { "title": "Toggle fullscreen" } }), _c("button", { staticClass: "pswp__button pswp__button--zoom", attrs: { "title": "Zoom in/out" } }), _vm.thumbnails ? _c("button", { staticClass: "pswp__button pswp__button--thumb", attrs: { "title": "Thumbnails Show/hide" }, on: { "click": _vm.togglePreview } }, [_c("span", { staticClass: "dashicons dashicons-screenoptions" })]) : _vm._e(), _vm._m(1)]), _vm._m(2), _c("button", { staticClass: "pswp__button pswp__button--arrow--left", attrs: { "title": "Previous (arrow left)" } }), _c("button", { staticClass: "pswp__button pswp__button--arrow--right", attrs: { "title": "Next (arrow right)" } }), _c("div", { staticClass: "pswp__previews", attrs: { "data-previews": "data-previews" } }), _vm._m(3)])])]);
  };
  var _sfc_staticRenderFns = [function() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "pswp__container" }, [_c("div", { staticClass: "pswp__item" }), _c("div", { staticClass: "pswp__item" }), _c("div", { staticClass: "pswp__item" })]);
  }, function() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "pswp__preloader" }, [_c("div", { staticClass: "pswp__preloader__icn" }, [_c("div", { staticClass: "pswp__preloader__cut" }, [_c("div", { staticClass: "pswp__preloader__donut" })])])]);
  }, function() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "pswp__share-modal pswp__share-modal--hidden pswp__single-tap" }, [_c("div", { staticClass: "pswp__share-tooltip" })]);
  }, function() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "pswp__caption" }, [_c("div", { staticClass: "pswp__caption__center" })]);
  }];
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns,
    false,
    null,
    null
  );
  const PhotoSwipeComponent = __component__.exports;
  if ("lightbox" in wcsvi.options && parseInt(wcsvi.options.lightbox)) {
    let $vm;
    const PhotoSwipe2 = Vue.extend(PhotoSwipeComponent);
    if (!$vm) {
      $vm = new PhotoSwipe2({
        el: document.createElement("div")
      });
    }
    Vue.$photoswipe = {
      open($item, $gallery, $photoEl, $pid) {
        document.body.appendChild($vm.$el);
        $vm.open($item, $gallery, $photoEl, $pid);
      },
      close() {
        $jq($vm.$el).remove();
      }
    };
    Vue.mixin({
      created() {
        this.$photoswipe = Vue.$photoswipe;
      }
    });
  }
  Vue.config.productionTip = false;
  window.Vue = Vue;
  window.jQuery = window.$jq = jQuery;
  const portoCheck = () => {
    return wcsvi.options.template === "porto" && "use_skeleton_screen" in js_porto_vars && (js_porto_vars.use_skeleton_screen.includes("product") || js_porto_vars.use_skeleton_screen.includes("quickview"));
  };
  const initializeSVIGalleries = ($ajax) => {
    if ($jq(".svi_wrapper").length > 0) {
      $jq.each($jq(".svi_wrapper"), (index2, element) => {
        if (wcsvi.options.template === "Divi") {
          $jq(element).closest(".woocommerce-product-gallery").removeClass("woocommerce-product-gallery");
        }
        Vue.prototype.buildMainGallery(index2, element, MAIN, $ajax);
      });
    }
    if ($jq(".svi_wrapper_drop").length > 0) {
      $jq.each($jq(".svi_wrapper_drop"), (index2, element) => {
        Vue.prototype.buildMainGallery(index2, element, THUMB, $ajax);
      });
    }
  };
  const setupQuickView = () => {
    if (!("quick_view" in wcsvi.options) || !parseInt(wcsvi.options.quick_view)) return;
    $jq(document).ajaxComplete((event, xhr, settings) => {
      if (settings.data && settings.data.indexOf("loadProduct") > 0) return;
      let productId = false;
      if ($jq(".svi_wrapper").length > 0 && settings.url.indexOf("admin-ajax.php") > -1) {
        if (wcsvi.options.template === "flatsome") {
          $jq(".product-lightbox .product-gallery-slider").remove();
        }
        initializeSVIGalleries(true);
      } else if (settings.url.indexOf("admin-ajax.php") > -1 && $jq(".woosvi_strap").length < 1) {
        if (wcsvi.options.quick_view_target.trim() !== "") {
          const $target = $jq(event.currentTarget.body);
          const $loadedTarget = $target.find('[id^="product-"],[data-product-id]');
          if ($loadedTarget.length > 0) {
            productId = $loadedTarget.attr("id").replace(/\D/g, "") || $loadedTarget.data("product-id");
            const $targetParent = $target.find(wcsvi.options.quick_view_target).parent();
            if (wcsvi.options.template === "shopkeeper") {
              $jq(".cd-quick-view").addClass("shopkeeper-svi");
            }
            $target.find(wcsvi.options.quick_view_target).remove();
            if (parseInt(productId)) {
              jQuery.ajax({
                url: wcsvi.call,
                type: "POST",
                data: {
                  action: "loadProduct",
                  id: productId
                }
              }).done(() => {
                productId = false;
              }).complete((response) => {
                $targetParent.prepend(response);
                initializeSVIGalleries(true);
              });
            }
          }
        }
      }
    });
  };
  const setupLoopShowcase = () => {
    if (!parseInt(wcsvi.options.loop_showcase)) return;
    const $loopEl = "loop_showcase_wrapper_el" in wcsvi.options && wcsvi.options.loop_showcase_wrapper_el ? wcsvi.options.loop_showcase_wrapper_el : ".product";
    const $loopImg = "loop_showcase_wrapper_el_img" in wcsvi.options && wcsvi.options.loop_showcase_wrapper_el_img ? wcsvi.options.loop_showcase_wrapper_el_img : "img";
    $jq($loopEl).each(function() {
      const $imageContainer = $jq(this).find($loopImg).first().parent();
      $imageContainer.attr("data-default-background", $imageContainer.css("background") || "none");
    });
    if (!("status" in wcsvi.options) || !wcsvi.options.status) {
      if ($jq(".svitn_img").length > 0) {
        $jq($loopEl).each(function() {
          if ($jq(this).find(".svitn_img").length > 0) {
            $jq(this).find(".svitn_img").eq(1).nextAll().remove();
          }
        });
      }
    }
    if ($jq(".svitn_img").length > 0) {
      const handleImageInteraction = function() {
        const $parentProduct = $jq(this).closest($loopEl);
        let $imageContainer = $parentProduct.find($loopImg).first().parent();
        if ($imageContainer.is("li")) {
          $imageContainer = $imageContainer.closest("ul").parent();
        }
        $jq(".svitn_current_img").removeClass("svitn_current_img");
        $jq(this).addClass("svitn_current_img");
        loadImage($imageContainer, this, $jq(this).index());
      };
      $jq(".svitn_img").on("mouseenter", handleImageInteraction);
      $jq(".svitn_img").on("click", handleImageInteraction);
      $jq(".svitn_img").on("mouseleave", function() {
        const $parentProduct = $jq(this).closest($loopEl);
        let $imageContainer = $parentProduct.find($loopImg).first().parent();
        if ($imageContainer.is("li")) {
          $imageContainer = $imageContainer.closest("ul").parent();
        }
        const $accordionContainer = $parentProduct.find(".svi-image-compare");
        $jq(".svitn_current_img").removeClass("svitn_current_img");
        if ($accordionContainer.length > 0) {
          const defaultBackground = $imageContainer.attr("data-default-background") || "none";
          $imageContainer.css("background", defaultBackground);
          $imageContainer.find("*").not(".svitn_wrapper,.svitn_img").css({ opacity: "" });
        } else {
          const defaultBackground = $imageContainer.attr("data-default-background") || "none";
          $imageContainer.css("background", defaultBackground);
          $imageContainer.find("*").not(".svitn_wrapper,.svitn_img").removeAttr("style");
        }
      });
      const loadImage = ($imageContainer, imgElement, index2) => {
        const imageSrc = $jq(imgElement).data("large_image");
        if (!imageSrc || typeof imageSrc !== "string" || !imageSrc.trim()) {
          console.error("SVI: Loop showcase - Invalid image URL:", imageSrc);
          return;
        }
        $imageContainer.css({
          background: `url(${imageSrc}) no-repeat center center`,
          "background-size": "cover",
          display: "block"
        });
        $imageContainer.find("*").not(".svitn_wrapper,.svitn_img").css({ opacity: "0" });
      };
    }
  };
  const setupFilterAttributeAccordion = () => {
    if (!("filter_attribute" in wcsvi.options) || !parseInt(wcsvi.options.filter_attribute)) return;
    const initializeAccordion = ($elements) => {
      $elements.each(function() {
        const $container = $jq(this);
        if ($container.hasClass("svi-initialized")) return;
        const $items = $container.find("> li");
        const layoutType = $container.data("cut") || "diagonal";
        const enableAnimation = wcsvi.options.filter_attribute_animation === "1";
        let referenceHeight = 0;
        const $productItem = $container.closest("li.wc-block-product, li.product");
        const $nonAccordionProductItem = $productItem.siblings("li.wc-block-product, li.product").not(":has(.svi-image-compare)").first();
        const $nonAccordionProduct = $nonAccordionProductItem.find("img").first();
        if ($nonAccordionProduct.length > 0) {
          referenceHeight = $nonAccordionProduct.height();
          Vue.prototype.consoleLog("referenceHeight", referenceHeight);
        }
        const $parentLink = $container.closest("li.wc-block-product, li.product");
        const parentHeight = $parentLink.length > 0 ? $parentLink.outerHeight(true) : 0;
        const defaultHeight = 231;
        const containerHeight = referenceHeight > 0 ? referenceHeight : parentHeight > 0 ? parentHeight : defaultHeight;
        $container.css("height", `${containerHeight}px`);
        $container.data("initial-height", containerHeight);
        $container.css({
          "--count": $items.length,
          "--width": $container.width() + "px"
        });
        const numImages = $items.length;
        const initialWidth = 100 / numImages;
        const reducedWidth = 10;
        const expandedWidth = 100 - (numImages - 1) * reducedWidth;
        $items.each(function(index2) {
          const $item = $jq(this);
          const imageUrl = $item.data("image");
          $item.css({
            "background-image": `url("${imageUrl}")`,
            "background-position": "center center",
            "background-size": "cover",
            "background-repeat": "no-repeat"
          });
          if (layoutType === "vertical") {
            $item.css({
              "width": "100%",
              "height": `${containerHeight / numImages}px`,
              // Explicitly set pixel height based on container height
              "position": "static"
            });
            if (enableAnimation) {
              $item.on("mouseenter", function() {
                $items.each(function(i) {
                  const $currentItem = $jq(this);
                  if (i === index2) {
                    $currentItem.css({
                      "height": "85%",
                      // Expand to 85% height
                      "z-index": 2
                      // Bring to top on hover
                    });
                  } else {
                    const remainingSpace = 15;
                    const nonHoveredHeight = remainingSpace / (numImages - 1);
                    $currentItem.css({
                      "height": `${nonHoveredHeight}%`,
                      // Split the remaining 15% space evenly
                      "z-index": 1
                      // Maintain lower z-index for non-hovered images
                    });
                    if (i < index2) {
                      $currentItem.css("top", `${i * nonHoveredHeight}%`);
                    } else {
                      const topPosition = 85 + (i - index2) * nonHoveredHeight;
                      $currentItem.css("top", `${topPosition}%`);
                    }
                  }
                });
              });
              $item.on("mouseleave", function() {
                $items.each(function(i) {
                  const $currentItem = $jq(this);
                  $currentItem.css({
                    "height": `${containerHeight / numImages}px`,
                    // Restore default height
                    "top": "auto",
                    // Reset top position
                    "z-index": 1
                    // Reset z-index
                  });
                });
              });
            }
          } else if (layoutType === "horizontal") {
            const leftPosition = index2 * initialWidth;
            $item.css({
              "width": `${initialWidth}%`,
              "left": `${leftPosition}%`
            });
            if (enableAnimation) {
              $item.on("mouseenter", function() {
                $items.each(function(i) {
                  const $currentItem = $jq(this);
                  if (i === index2) {
                    $currentItem.css({
                      "width": `${expandedWidth}%`,
                      "z-index": 2
                    });
                    if (i === 0) {
                      $currentItem.css("left", "0%");
                    } else if (i === numImages - 1) {
                      $currentItem.css("left", `${100 - expandedWidth}%`);
                    } else {
                      const totalReducedWidthBefore = i * reducedWidth;
                      const leftPosition2 = totalReducedWidthBefore;
                      $currentItem.css("left", `${leftPosition2}%`);
                    }
                  } else {
                    $currentItem.css({
                      "width": `${reducedWidth}%`,
                      "z-index": 1
                    });
                    if (i < index2) {
                      $currentItem.css("left", `${i * reducedWidth}%`);
                    } else {
                      const leftPosition2 = expandedWidth + (i - index2 - 1) * reducedWidth + index2 * reducedWidth;
                      $currentItem.css("left", `${leftPosition2}%`);
                    }
                  }
                });
              });
              $item.on("mouseleave", function() {
                $items.each(function(i) {
                  const $currentItem = $jq(this);
                  const leftPosition2 = i * initialWidth;
                  $currentItem.css({
                    "width": `${initialWidth}%`,
                    "left": `${leftPosition2}%`,
                    "z-index": 1
                  });
                });
              });
            }
          } else {
            Vue.prototype.consoleLog(`SVI: Processing diagonal layout with ${numImages} images`);
            let cutPercentage;
            if (numImages === 2) {
              cutPercentage = 90;
            } else if (numImages === 3) {
              cutPercentage = 80;
            } else {
              cutPercentage = 70;
            }
            $container.css("--cut-percentage", `${cutPercentage}%`);
            const leftPosition = index2 * initialWidth;
            $item.css({
              "width": `${initialWidth + 10}%`,
              // Increase width to overlap and fill gaps
              "left": `${leftPosition}%`,
              "z-index": numImages - index2
              // Higher index gets lower z-index
            });
            if (enableAnimation) {
              $item.on("mouseenter", function() {
                $items.each(function(i) {
                  const $currentItem = $jq(this);
                  if (i === index2) {
                    $currentItem.css({
                      "width": "85%",
                      // Expand to 85% width
                      "z-index": numImages + 1,
                      // Bring to top on hover
                      "clip-path": "none",
                      // Remove diagonal cut on hover
                      "left": "7.5%"
                      // Center the expanded image (15% remaining space, split as 7.5% on each side)
                    });
                  } else {
                    $currentItem.css({
                      "width": `${7.5}%`,
                      // Split the remaining 15% space (7.5% each side)
                      "z-index": numImages - i,
                      // Maintain original z-index for non-hovered images
                      "clip-path": "none",
                      // Remove diagonal cut on non-hovered images during hover
                      "visibility": "visible"
                      // Ensure non-hovered images remain visible
                    });
                    if (i < index2) {
                      $currentItem.css("left", `${i * 7.5}%`);
                    } else {
                      const leftPosition2 = 85 + 7.5 + (i - index2 - 1) * 7.5;
                      $currentItem.css("left", `${leftPosition2}%`);
                    }
                  }
                });
              });
              $item.on("mouseleave", function() {
                $items.each(function(i) {
                  const $currentItem = $jq(this);
                  const leftPosition2 = i * initialWidth;
                  $currentItem.css({
                    "width": `${initialWidth + 10}%`,
                    "left": `${leftPosition2}%`,
                    "z-index": numImages - i,
                    "visibility": "visible"
                    // Ensure all images remain visible
                  });
                  if (i === numImages - 1) {
                    $currentItem.css("clip-path", "none");
                  } else {
                    $currentItem.css("clip-path", `polygon(0 0, 100% 0, ${cutPercentage}% 100%, 0 100%)`);
                  }
                });
              });
            }
          }
        });
        $container.addClass("svi-initialized");
      });
    };
    initializeAccordion($jq(".svi-image-compare"));
    const observer2 = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
          const $newElements = $jq(mutation.addedNodes).find(".svi-image-compare").addBack(".svi-image-compare");
          if ($newElements.length) {
            initializeAccordion($newElements);
          }
        }
      });
    });
    observer2.observe(document.body, {
      childList: true,
      subtree: true
    });
    $jq(window).on("resize", function() {
      setTimeout(() => {
        $jq(".svi-image-compare").each(function() {
          const $container = $jq(this);
          $container.css({
            "--width": $container.width() + "px"
          });
        });
      }, 1);
    });
  };
  const initializeSVI = () => {
    if (portoCheck()) {
      if ($jq("body").hasClass("single-product")) {
        Vue.prototype.waitForEl(".svi_wrapper", initializeSVIGalleries, 100, 50);
      } else {
        initializeSVIGalleries();
      }
    } else {
      initializeSVIGalleries();
    }
  };
  const init = () => {
    setupQuickView();
    setupLoopShowcase();
    setupFilterAttributeAccordion();
    initializeSVI();
  };
  const relocateGalleryIntoGutenberg = () => {
    const moveGallery = () => {
      const gutenbergGalleryBlock = document.querySelector(
        '[data-block-name="woocommerce/product-image-gallery"]'
      );
      const sviGallery = document.querySelector(".gallery-svi.woocommerce-product-gallery");
      if (gutenbergGalleryBlock && sviGallery && !gutenbergGalleryBlock.contains(sviGallery)) {
        gutenbergGalleryBlock.appendChild(sviGallery);
      }
    };
    document.addEventListener("DOMContentLoaded", moveGallery);
    const observer2 = new MutationObserver(() => moveGallery());
    observer2.observe(document.body, { childList: true, subtree: true });
  };
  relocateGalleryIntoGutenberg();
  init();
})();
//# sourceMappingURL=smart-variations-images-public.js.map
