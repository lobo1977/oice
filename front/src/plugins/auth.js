import Vue from 'vue'
import HttpRequest from './http'

Vue.use(HttpRequest)

export default {
  install (Vue, options) {
    Vue.login = (mobile, password, vcode, verifyCode, cb) => {
      Vue.post('/api/login', {
        mobile: mobile,
        password: password,
        vcode: vcode,
        verifyCode: verifyCode
      }, (res) => {
        if (res.success) {
          localStorage.user = JSON.stringify(res.data)
        }
        if (cb) cb(res)
      })
    }

    Vue.mobile = (mobile, verifyCode, cb) => {
      Vue.post('/api/mobile', {
        mobile: mobile,
        verifyCode: verifyCode
      }, (res) => {
        if (res.success) {
          localStorage.user = JSON.stringify(res.data)
        }
        if (cb) cb(res)
      })
    }

    Vue.sendVerifyCode = (mobile, cb) => {
      Vue.post('/api/sendVerifyCode', {
        mobile: mobile
      }, (res) => {
        if (cb) cb(res)
      })
    }

    Vue.getUser = () => {
      if (localStorage.user) {
        try {
          let user = JSON.parse(localStorage.user)
          let expireTime = 0
          if (isNaN(user.expire_time)) {
            expireTime = new Date(user.expire_time).getTime() / 1000
          } else {
            expireTime = Number(user.expire_time)
          }
          let now = Math.round(new Date().getTime() / 1000)

          if (expireTime <= now + 21600) {
            Vue.get('/api/updateToken', (res) => {
              if (res.success) {
                user.token = res.data.token
                user.expire_time = res.data.expire_time
                localStorage.user = JSON.stringify(user)
              } else {
                delete localStorage.user
                return null
              }
            })
            if (expireTime < now) {
              delete localStorage.user
              return null
            }
          }
          return user
        } catch (e) {
          delete localStorage.user
          return null
        }
      } else {
        return null
      }
    }

    Vue.setUser = (user) => {
      localStorage.user = JSON.stringify(user)
    }

    Vue.updateUser = (cb) => {
      Vue.get('/api/getUserInfo', (res) => {
        if (res.success) {
          localStorage.user = JSON.stringify(res.data)
        }
        if (cb) cb(res)
      })
    }

    Vue.logout = (cb) => {
      Vue.get('/api/logout')
      delete localStorage.user
      if (cb) cb()
    }

    Vue.prototype.$login = (mobile, password, vcode, verifyCode, cb) => {
      Vue.login(mobile, password, vcode, verifyCode, cb)
    }

    Vue.prototype.$mobile = (mobile, verifyCode, cb) => {
      Vue.mobile(mobile, verifyCode, cb)
    }

    Vue.prototype.$sendVerifyCode = (mobile, cb) => {
      Vue.sendVerifyCode(mobile, cb)
    }

    Vue.prototype.$getUser = () => {
      return Vue.getUser()
    }

    Vue.prototype.$setUser = (user) => {
      Vue.setUser(user)
    }

    Vue.prototype.$updateUser = (cb) => {
      Vue.updateUser(cb)
    }

    Vue.prototype.$logout = (cb) => {
      Vue.logout(cb)
    }
  }
}
