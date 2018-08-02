import Vue from 'vue'
import Vuex from 'vuex'
import FastClick from 'fastclick'
import HttpRequest from '@/plugins/http'
import Authenticate from '@/plugins/auth'
import Wechat from '@/plugins/wechat'
import App from './App'
import router from './router/index'
import { LoadingPlugin, ToastPlugin, AlertPlugin, ConfirmPlugin } from 'vux'

Vue.config.productionTip = false

Vue.use(Vuex)
Vue.use(LoadingPlugin)
Vue.use(ToastPlugin, {type: 'text', position: 'middle'})
Vue.use(AlertPlugin)
Vue.use(ConfirmPlugin)
Vue.use(HttpRequest)
Vue.use(Authenticate)
Vue.use(Wechat)

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

router.beforeEach(function (to, from, next) {
  store.commit('updateLoadingStatus', {isLoading: true})
  if (to.name === 'Logout') {
    Vue.logout(() => {
      store.commit('setUser', {user: null})
      next({name: 'Login', replace: true})
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
    if (Vue.isWechat()) {
      document.location.href = '/api/wechat/login?redirect=' + encodeURI(to.fullPath)
      next(false)
    } else {
      next({
        name: 'Login',
        query: {redirect: to.fullPath}
      })
    }
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
