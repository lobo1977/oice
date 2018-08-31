import axios from 'axios'
import qs from 'qs'

function ajax (url, method, data, contentType, cb) {
  let headers = {
    'Content-Type': contentType || 'application/x-www-form-urlencoded;charset=utf-8'
  }

  if (url.indexOf('/api/') === 0 && localStorage.user) {
    try {
      let user = JSON.parse(localStorage.user)
      if (user && user.token) {
        headers['User-Token'] = user.token
      }
    } catch (e) {}
  }

  const $axios = axios.create({
    headers: headers,
    transformRequest: function (data) {
      if (headers['Content-Type'].indexOf('x-www-form-urlencoded') > 0) {
        return qs.stringify(data)
      } else {
        return data
      }
    }
  })

  $axios({ url, method, data })
    .then(response => {
      if (cb) cb(response.data)
    })
    .catch(error => {
      let err = {
        success: false,
        message: '系统异常。',
        code: 0
      }
      if (error.response) {
        err.code = error.response.status
        if (error.response.data && error.response.data.message) {
          err.message = error.response.data.message
        } else {
          switch (error.response.status) {
            case 400:
              err.message = '请求失败。'
              break
            case 401:
              delete localStorage.user
              err.message = '访问的资源未授权，请登录。'
              break
            case 403:
              err.message = '拒绝访问。'
              break
            case 404:
              err.message = '访问的资源不存在。'
              break
            case 408:
              err.message = '请求超时。'
              break
            case 422:
              err.message = '请求参数无效。'
              break
            case 500:
              err.message = '服务器内部错误。'
              break
            case 501:
              err.message = '服务未实现。'
              break
            case 502:
              err.message = '网关错误。'
              break
            case 503:
              err.message = '服务不可用。'
              break
            case 504:
              err.message = '网关超时。'
              break
            case 505:
              err.message = 'HTTP版本不支持。'
              break
            default:
              err.message = error.response.status
          }
        }
      }
      if (cb) cb(err)
    })
}

export default {
  install (Vue, options) {
    Vue.httpRequest = (url, method, data, cb) => {
      ajax(url, method, data, null, cb)
    }

    Vue.post = (url, data, cb) => {
      ajax(url, 'post', data, null, cb)
    }

    Vue.get = (url, cb) => {
      ajax(url, 'get', null, null, cb)
    }

    Vue.postFile = (url, form, cb) => {
      let data = null
      if (form) {
        const maxBytes = 6291456
        for (let i in form.elements) {
          if (form.elements[i].type === 'file') {
            for (let f in form.elements[i].files) {
              if (form.elements[i].files[f].size > maxBytes) {
                if (cb) {
                  cb.call(this, {
                    success: false,
                    message: '上传文件不能超过6兆字节。',
                    code: 0
                  })
                }
                return
              }
            }
          }
        }
        data = new FormData(form)
      }
      ajax(url, 'post', data, 'multipart/form-data', cb)
    }

    Vue.prototype.$httpRequest = (url, method, data, cb) => {
      Vue.httpRequest(url, method, data, cb)
    }

    Vue.prototype.$post = (url, data, cb) => {
      Vue.post(url, data, cb)
    }

    Vue.prototype.$get = (url, cb) => {
      Vue.get(url, cb)
    }

    Vue.prototype.$postFile = (url, form, cb) => {
      Vue.postFile(url, form, cb)
    }
  }
}
