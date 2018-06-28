import Vue from 'vue'
import Vuex from 'vuex'
import FastClick from 'fastclick'
import App from './App'
import HttpRequest from './plugins/http'
import router from './plugins/router'
import Authenticate from './plugins/auth'
import { LoadingPlugin, ToastPlugin, AlertPlugin, ConfirmPlugin, WechatPlugin } from 'vux'

Vue.config.productionTip = false

Vue.use(Vuex)
Vue.use(LoadingPlugin)
Vue.use(ToastPlugin, {type: 'text', position: 'middle'})
Vue.use(AlertPlugin)
Vue.use(ConfirmPlugin)
Vue.use(HttpRequest)
Vue.use(Authenticate)
Vue.use(WechatPlugin)

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
    Vue.logout()
    store.commit('setUser', {user: null})
    next({name: 'Login', replace: true})
  } else if (window.history.length <= 1 && !to.query.index && !from.query.index) {
    let query = to.query
    Vue.set(query, 'index', 'true')
    next({name: to.name, params: to.params, query: query, replace: true})
  } else if (to.matched.some(record => record.meta.requiresAuth)) {
    if (store.state.oice.user == null) {
      next({
        name: 'Login',
        query: {redirect: to.fullPath}
      })
    } else {
      next()
    }
  } else {
    next()
  }
})

router.afterEach(function (to) {
  store.commit('updateLoadingStatus', {isLoading: false})
})

// 微信接口注册
Vue.prototype.$wechatConfig = function (link, title, desc, image) {
  Vue.post('/api/wechat/config', {
    url: window.location.href
  }, (res) => {
    if (res.success) {
      Vue.wechat.config(res.data)

      Vue.wechat.error((res) => {
      })

      Vue.wechat.ready(() => {
        Vue.wechat.onMenuShareTimeline({
          title: title,
          link: link,
          imgUrl: image
        })

        Vue.wechat.onMenuShareAppMessage({
          title: title,
          desc: desc,
          link: link,
          imgUrl: image
        })

        Vue.wechat.onMenuShareQQ({
          title: title,
          desc: desc,
          link: link,
          imgUrl: image
        })
      })
    }
  })
}

new Vue({
  store,
  router,
  render: h => h(App)
}).$mount('#app')
