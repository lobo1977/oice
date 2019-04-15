import Vue from 'vue'
import Vuex from 'vuex'
import FastClick from 'fastclick'
import HttpRequest from '@/plugins/http'
import Authenticate from '@/plugins/auth'
import Wechat from '@/plugins/wechat'
import App from './App'
import router from './router/index'
import { dateFormat, LoadingPlugin, ToastPlugin, AlertPlugin, ConfirmPlugin, DatetimePlugin,
  Panel, Sticky, Tab, TabItem, Group, Cell, LoadMore, Flexbox, FlexboxItem,
  Actionsheet, Popup, PopupPicker, Badge, Divider,
  XButton, XInput, XSwitch, InlineXNumber, XTextarea } from 'vux'

Vue.config.productionTip = false

Vue.use(Vuex)
Vue.use(LoadingPlugin)
Vue.use(ToastPlugin, {type: 'text', position: 'middle'})
Vue.use(AlertPlugin)
Vue.use(ConfirmPlugin)
Vue.use(DatetimePlugin)
Vue.use(HttpRequest)
Vue.use(Authenticate)
Vue.use(Wechat)

Vue.component('panel', Panel)
Vue.component('sticky', Sticky)
Vue.component('tab', Tab)
Vue.component('tab-item', TabItem)
Vue.component('group', Group)
Vue.component('cell', Cell)
Vue.component('load-more', LoadMore)
Vue.component('flexbox', Flexbox)
Vue.component('flexbox-item', FlexboxItem)
Vue.component('actionsheet', Actionsheet)
Vue.component('popup', Popup)
Vue.component('popup-picker', PopupPicker)
Vue.component('badge', Badge)
Vue.component('divider', Divider)
Vue.component('x-button', XButton)
Vue.component('x-input', XInput)
Vue.component('inline-x-number', InlineXNumber)
Vue.component('x-textarea', XTextarea)
Vue.component('x-switch', XSwitch)

Vue.prototype.$dateFormat = dateFormat

Vue.filter('formatDate', function (value) {
  if (value) {
    return dateFormat(new Date(Date.parse(value.replace(/-/g, '/'))), 'YYYY年M月D日')
  } else {
    return value
  }
})

Vue.filter('formatTime', function (value) {
  if (value) {
    let time = new Date(Date.parse(value.replace(/-/g, '/')))
    let now = new Date()
    let timeDiff = now - time

    if (timeDiff < 0) {
      if (now.getFullYear() !== time.getFullYear()) {
        return dateFormat(time, 'YYYY年M月D日 HH:mm')
      } else if (now.getDate() !== time.getDate()) {
        return dateFormat(time, 'M月D日 HH:mm')
      } else {
        return dateFormat(time, 'HH:mm')
      }
    } else if (timeDiff < 1000 * 60 * 3) {
      return '刚刚'
    } else if (timeDiff < 1000 * 60 * 59) {
      return Math.round(timeDiff / 1000 / 60) + '分钟前'
    } else if (timeDiff < 1000 * 60 * 60 * 24) {
      return dateFormat(time, 'HH:mm')
    } else if (timeDiff < 1000 * 60 * 60 * 24 * 180 ||
      now.getFullYear() === time.getFullYear()) {
      return dateFormat(time, 'M月D日 HH:mm')
    } else {
      return dateFormat(time, 'YYYY年M月D日 HH:mm')
    }
  } else {
    return value
  }
})

Vue.prototype.$download = (url) => {
  Vue.$vux.loading.show()
  let iframe = document.createElement('iframe')
  iframe.style.display = 'none'
  iframe.src = url
  document.body.appendChild(iframe)
  let timer = setInterval(() => {
    let iframeDoc = iframe.contentDocument || iframe.contentWindow.document
    if (iframeDoc && (iframeDoc.readyState === 'complete' ||
      iframeDoc.readyState === 'interactive')) {
      document.body.removeChild(iframe)
      Vue.$vux.loading.hide()
      clearInterval(timer)
    }
  }, 500)
}

FastClick.attach(document.body)

let store = new Vuex.Store({})

store.registerModule('oice', {
  state: {
    user: Vue.getUser(),
    isLoading: false,
    direction: 'forward',
    scrollTop: {},
    scrollBottom: false
  },
  mutations: {
    getUser (state) {
      state.user = Vue.getUser()
    },
    setUser (state, payload) {
      Vue.setUser(payload.user)
      state.user = payload.user
    },
    updateLoadingStatus (state, payload) {
      state.isLoading = payload.isLoading
    },
    updateScrollPosition (state, payload) {
      Vue.set(state.scrollTop, payload.path, payload.top)
      state.scrollBottom = payload.isBottom
    }
  },
  actions: {
    setUser ({commit}, user) {
      commit({type: 'setUser', user: user})
    },
    getUser ({commit}) {
      commit({type: 'getUser'})
    },
    updateScrollPosition ({commit}, position) {
      commit({type: 'updateScrollPosition', path: position.path, top: position.top, isBottom: position.isBottom})
    }
  }
})

Vue.prototype.$checkAuth = () => {
  if (store.state.oice.user == null) {
    let path = router.currentRoute.fullPath
    // if (Vue.isWechat()) {
    //   window.location.href = '/api/wechat/login?redirect=' + encodeURI(path)
    // } else {
    router.push({
      name: 'Login',
      query: { redirect: path }
    })
    // }
    return false
  }
  return true
}

router.beforeEach(function (to, from, next) {
  store.commit('updateLoadingStatus', {isLoading: true})
  if (to.name === 'Logout') {
    Vue.logout(() => {
      store.commit('setUser', {user: null})
      next({name: 'Home', replace: true})
    })
  } else if (to.name === 'WechatLogin') {
    let query = to.query
    if (query.code && query.state) {
      Vue.post('/api/wechat/user', {
        code: query.code,
        state: query.state
      }, (res) => {
        if (res.success) {
          if (res.data && res.data.id) {
            store.commit('setUser', {user: res.data})
            if (res.data.redirect) {
              next({path: res.data.redirect, replace: true})
            } else {
              next({name: 'My', replace: true})
            }
          } else {
            next({name: 'BindMobile', query: res.data, replace: true})
          }
        } else {
          next({name: 'Login', replace: true})
        }
      })
    } else {
      next({name: 'Login', replace: true})
    }
  } else if (window.history.length <= 1 && !to.query.index && !from.query.index) {
    let query = to.query
    Vue.set(query, 'index', 'true')
    next({name: to.name, params: to.params, query: query, replace: true})
  } else if (to.matched.some(record => record.meta.requiresAuth) &&
    store.state.oice.user == null) {
    // if (Vue.isWechat()) {
    //   window.location.href = '/api/wechat/login?redirect=' + encodeURI(to.fullPath)
    //   next(false)
    // } else {
    next({
      name: 'Login',
      query: {redirect: to.fullPath}
    })
    // }
  } else {
    next()
  }
})

router.afterEach(function (to) {
  store.commit('updateLoadingStatus', {isLoading: false})
  if (Vue.isWechat() && (!to.meta || !to.meta.canShare)) {
    let url = window.location.protocol + '//' +
      window.location.host + '/app' + to.fullPath
    Vue.wechatHideShare(url)
  }
})

new Vue({
  store,
  router,
  render: h => h(App)
}).$mount('#app')
