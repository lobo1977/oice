// pages/my/mobile/mobile.js

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    waiting: false,
    seconds: 60,
    timer: null,
    mobile: '',
    is_mobile_empty: false,
    mobile_empty: '',
    mobile_error: '',
    code: '',
    is_code_empty: false,
    code_empty: '',
    send_code_disabled: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  },

  onMobileInput: function(event) {
    this.data.mobile = event.detail
    if (this.data.mobile) {
      if (app.isMobile(event.detail)) {
        this.setData({
          is_mobile_empty: false,
          mobile_empty: '',
          mobile_error: ''
        })
      } else {
        this.setData({
          is_mobile_empty: false,
          mobile_empty: ''
        })
      }
    }
  },

  onCodeInput: function(event) {
    this.data.code = event.detail
    if (this.data.code) {
      that.setData({
        is_code_empty: false,
        code_empty: ''
      })
    }
  },

  /**
   * 发送短信验证码
   */
  bindSendCode: function(event) {
    let that = this
    if (!that.data.mobile) {
      that.setData({
        is_mobile_empty: true,
        mobile_empty: '请填写手机号码'
      })
      return
    } else if (!app.isMobile(that.data.mobile)) {
      that.setData({
        mobile_error: '手机号码错误'
      })
      return
    }

    that.setData({
      send_code_disabled: true
    })

    wx.showLoading({
      title: '发送中',
    })

    app.post('sendVerifyCode', {
      mobile: that.data.mobile
    }, (res) => {
      if (res.success) {
        that.setData({
          waiting: true
        })
        that.data.timer = setInterval(function() {
          let s = that.data.seconds - 1
          that.setData({
            seconds: s
          })
          if (s <= 0) {
            clearInterval(that.data.timer)
            that.setData({
              waiting: false,
              seconds: 60
            })
          }
        }, 1000)
      } else {
        if (res.message) {
          wx.showToast({
            icon: 'none',
            title: res.message,
            duration: 2000
          })
        } else {
          wx.showToast({
            icon: 'none',
            title: '操作失败，系统异常',
            duration: 2000
          })
        }
      }
    }, () => {
      that.setData({
        send_code_disabled: true
      })
      wx.hideLoading()
    })
  },

  /**
   * 提交绑定
   */
  bindSave: function(event) {
    let that = this
    if (!that.data.mobile) {
      that.setData({
        is_mobile_empty: true,
        mobile_empty: '请填写手机号码'
      })
      return
    } else if (!app.isMobile(that.data.mobile)) {
      that.setData({
        mobile_error: '手机号码错误'
      })
      return
    } else if (!that.data.code) {
      that.setData({
        is_code_empty: true,
        code_empty: '请填写验证码'
      })
      return
    }

    wx.showLoading({
      title: '提交中',
    })

    app.post('my/mobile', {
      mobile: that.data.mobile,
      verifyCode: that.data.code
    }, (res) => {
      if (res.success) {
        if (res.data) {
          app.globalData.appUserInfo = res.data
        }
        app.goBack()
      } else {
        if (res.message) {
          wx.showToast({
            icon: 'none',
            title: res.message,
            duration: 2000
          })
        } else {
          wx.showToast({
            icon: 'none',
            title: '操作失败，系统异常',
            duration: 2000
          })
        }
      }
    }, () => {
      wx.hideLoading()
    })
  }
})