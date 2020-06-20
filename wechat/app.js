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
    serverUrl: 'https://m.o-ice.com',
    area: [
      {
        text: "所有区域",
        id: "all",
        children: [
        ]
      },
      {
        text: "东城区",
        id: "东城区",
        children: [
          {
            text: "全区",
            id: ""
          },
          {
            text: "建国门",
            id: "建国门"
          },
          {
            text: "东直门",
            id: "东直门"
          },
          {
            text: "长安街",
            id: "长安街"
          },
          {
            text: "崇文门",
            id: "崇文门"
          }
        ]
      },
      {
        text: "西城区",
        id: "西城区",
        children: [
          {
            text:"全区",
            id: ""
          },
          {
            text:"金融街",
            id: "金融街"
          },
          {
            text:"西直门",
            id: "西直门"
          },
          {
            text:"西单",
            id: "西单"
          },
          {
            text:"复兴门",
            id: "复兴门"
          },
          {
            text:"宣武门",
            id: "宣武门"
          }
        ]
      },
      {
        text: "朝阳区",
        id: "朝阳区",
        children: [
          {
            text:"全区",
            id: ""
          },
          {
            text:"CBD",
            id: "CBD"
          },
          {
            text:"朝外",
            id: "朝外"
          },
          {
            text:"燕莎",
            id: "燕莎"
          },
          {
            text:"望京",
            id: "望京"
          },
          {
            text:"亚奥",
            id: "亚奥"
          }
        ]
      },
      {
        text: "海淀区",
        id: "海淀区",
        children: [
          {
            text:"全区",
            id: ""
          },
          {
            text:"中关村",
            id: "中关村"
          },
          {
            text:"清华科技园",
            id: "清华科技园"
          },
          {
            text:"上地",
            id: "上地"
          },
          {
            text:"公主坟",
            id: "公主坟"
          },
          {
            text:"马甸",
            id: "马甸"
          }
        ]
      },
      {
        text: "丰台区",
        id: "丰台区",
        children: [
          {
            text:"全区",
            id: ""
          },
          {
            text:"丽泽金融",
            id: "丽泽金融"
          },
          {
            text:"丰台科技园",
            id: "丰台科技园"
          }
        ]
      },
      {
        text: "石景山区",
        id: "石景山区",
        children: [
        ]
      },
      {
        text: "大兴区",
        id: "大兴区",
        children: [
          {
            text:"全区",
            id: ""
          },
          {
            text:"亦庄开发区",
            id: "亦庄开发区"
          }
        ]
      },
      {
        text: "通州区",
        id: "通州区",
        children: [
          {
            text:"全区",
            id: ""
          },
          {
            text:"副中心",
            id: "副中心"
          },
          {
            text:"环球影城",
            id: "环球影城"
          }
        ]
      },
      {
        text: "昌平区",
        id: "昌平区",
        children: [
        ]
      },
      {
        text: "顺义区",
        id: "顺义区",
        children: [
        ]
      },
      {
        text: "房山区",
        id: "房山区",
        children: [
        ]
      }
    ],
    buildingType: [
      {
        text: "类别",
        value: ""
      },{
        text: "写字楼",
        value: "写字楼"
      }, {
        text: "商铺独楼",
        value: "商铺独楼"
      }, {
        text: "商务中心",
        value: "商务中心"
      }, {
        text: "商住公寓",
        value: "商住公寓"
      }, {
        text: "产业园",
        value: "产业园"
      }, {
        text: "酒店",
        value: "酒店"
      }, {
        text: "购物中心",
        value: "购物中心"
      }, {
        text: "待认领",
        value: "empty"
      }
    ],
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
  			wx.showToast({
          title: '发生错误，请稍后再试。',
          icon: 'none',
          duration: 2000
        })
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
  },

  formatTime(time, fmt) {
    var time = new Date(time)
    var o = {
      "M+": time.getMonth() + 1, //月份
      "d+": time.getDate(), //日
      "h+": time.getHours(), //小时
      "H+": time.getHours(), //小时
      "m+": time.getMinutes(), //分
      "s+": time.getSeconds(), //秒
      "q+": Math.floor((time.getMonth() + 3) / 3), //季度
      "S": time.getMilliseconds() //毫秒
    }
    if (/(y+)/.test(fmt)) 
      fmt = fmt.replace(RegExp.$1, (time.getFullYear() + '').substr(4 - RegExp.$1.length))
    for (var k in o)
      if (new RegExp('(' + k + ')').test(fmt)) 
        fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (('00' + o[k]).substr(('' + o[k]).length)))
    return fmt
  },

  isMobile(str) {
    return /^1[3456789]\d{9}$/.test(str)
  },

  isEmail(str) {
    return /^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z0-9]{2,6}$/.test(str)
  }
})