//app.js
const API_URL = 'https://m.o-ice.com/api/'

App({
  onLaunch: function () {
    // 展示本地存储能力
    // var logs = wx.getStorageSync('logs') || []
    // logs.unshift(Date.now())
    // wx.setStorageSync('logs', logs)
    
    // 获取用户信息
    wx.getUserInfo({
      success: res => {
        this.globalData.userInfo = res.userInfo
        this.login()
        if (this.userInfoReadyCallback) {
          this.userInfoReadyCallback(res)
        }
      },
      fail: () => {
        this.login()
      }
    })
    
    // wx.getSetting({
    //   success: res => {
    //     if (res.authSetting['scope.userInfo']) {
    //     } else {
    //     }
    //   }
    // })
  },
  
  globalData: {
    userInfo: null,
    appUserInfo: null,
    serverUrl: 'https://m.o-ice.com'
  },
  
  // 登录
  login () {
    wx.login({
      success: res => {
        let app = this
        if (res.code) {
          app.post('wechat/miniLogin', { 
              code: res.code, 
              nickname: (app.globalData.userInfo ? app.globalData.userInfo.nickName : ''),
              avatar: (app.globalData.userInfo ? app.globalData.userInfo.avatarUrl : ''),
            }, (res2) => {
            app.globalData.appUserInfo = res2.data
            if (app.userLoginCallback) {
              app.userLoginCallback(res2)
            }
          })
        } else {
          console.log('登录失败！' + res.errMsg)
        }
      }
    })
  },
  
  request(url, method, data, cb, finish) {
      wx.request({
  		url: API_URL + url,
  		method: method,
  		data: data,
  		header: {
        'Content-Type': 'application/x-www-form-urlencoded',
  			'User-Token': this.globalData.appUserInfo && this.globalData.appUserInfo.token ? 
  				this.globalData.appUserInfo.token : ''
  		},
  		success (res) {
  			if (cb) {
  				cb(res.data)
  			}
  		},
  		fail (err) {
  			console.log(err)
  		},
  		complete () {
        if (finish) {
          finish()
        }
  		}
  	})
  },
  
  post(url, data, cb, finish) {
  	this.request(url, 'post', data, cb, finish)
  },
  
  get(url, cb, finish) {
  	this.request(url, 'get', null, cb, finish)
  }
})