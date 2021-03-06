// pages/my/info/info.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    avatar: null,
    mobile: '',
    info: {
      __token__: '',
      title: '',    // 姓名
      role: 0,      // 行业属性
      company: '',  // 企业   
      email: '',    // 电子邮箱
      weixin: '',   // 微信
      qq: ''        // QQ
    },
    role: [{name: '发展商', value : 1}, {name: '代理行', value: 2}, {name: '其他', value: 10}],
    role_name: '',
    is_title_empty : false,
    title_error: '',
    is_company_empty : false,
    company_error: '',
    is_email_error: false,
    email_error: '',
    showSelectRole: false
  },

  setInfo: function() {
    let role_name = ''
    if (app.globalData.appUserInfo.role) {
      this.data.role.forEach(element => {
        if (element.value == app.globalData.appUserInfo.role) {
          role_name = element.name
        }
      })
    }
    this.setData({
      avatar: app.globalData.appUserInfo.avatar,
      mobile: app.globalData.appUserInfo.mobile,
      role_name: role_name,
      ['info.title']: app.globalData.appUserInfo.title,
      ['info.company']: app.globalData.appUserInfo.company,
      ['info.role']: app.globalData.appUserInfo.role,
      ['info.email']: app.globalData.appUserInfo.email,
      ['info.weixin']: app.globalData.appUserInfo.weixin,
      ['info.qq']: app.globalData.appUserInfo.qq
    })

    app.get('index/token', (res) => {
      if (res.success && res.data) {
        this.data.info.__token__ = res.data
      }
    }, () => {
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (app.globalData.appUserInfo) {
      this.setInfo()
    } else {
      app.userLoginCallback = () => {
        this.setInfo()
      }
    }
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
    wx.stopPullDownRefresh()
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

  bindSelectRole: function() {
    this.setData({
      showSelectRole: true
    })
  },

  onSelectRoleClose: function() {
    this.setData({
      showSelectRole: false
    })
  },

  onRoleSelect: function(event) {
    this.setData({
      role_name: event.detail.name,
      ['info.role']: event.detail.value,
      showSelectRole: false
    })
  },

  onTitleInput: function(event) {
    this.data.info.title = event.detail
    if (this.data.info.title && this.data.is_title_empty) {
      this.setData({
        is_title_empty: false,
        title_error: ''
      })
    }
  },

  onCompanyInput: function(event) {
    this.data.info.company = event.detail
    if (this.data.info.company && this.data.is_company_empty) {
      this.setData({
        is_company_empty: false,
        company_error: ''
      })
    }
  },

  onEmailInput: function(event) {
    this.data.info.email = event.detail
    if (event.detail) {
      if (app.isEmail(event.detail)) {
        this.setData({
          is_email_error: false,
          email_error: ''
        })
      }
    }
  },

  onWeixinInput: function(event) {
    this.data.info.weixin = event.detail
  },

  onQQInput: function(event) {
    this.data.info.qq = event.detail
  },

  bindSave: function() {
    let that = this
    if (!that.data.info.title) {
      that.setData({
        is_title_empty: true,
        title_error: '请填写姓名'
      })
    }

    if (!that.data.info.company) { 
      that.setData({
        is_company_empty: true,
        company_error: '请填写公司'
      })
    }
    
    if (that.data.info.email && !app.isEmail(that.data.info.email)) {
      that.setData({
        is_email_error: true,
        email_error: '电子邮箱错误'
      })
    }

    if (that.data.is_title_empty || that.data.is_company_empty || that.data.is_email_error) {
      return
    }

    wx.showLoading({
      title: '保存中',
    })

    app.post('my/edit', that.data.info, (res) => {
      if (res.success) {
        if (res.data) {
          app.globalData.appUserInfo = res.data
        }
        app.goBack()
      } else {
        if (res.data) {
          that.data.info.__token__ = res.data
        }
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
  },

  upload: function(event) {
    let that = this
    let header = {
      'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
        app.globalData.appUserInfo.token : ''
    }
    if (!app.globalData.isWindows) {
      header['Content-Type'] =  'multipart/form-data'
    }
    const file = event.detail.file
    if (!file) {
      return
    }
    wx.showLoading()
    try {
      wx.uploadFile({
        header: header,
        url: app.globalData.serverUrl + '/api/my/edit',
        filePath: file.path,
        name: 'avatar',
        formData: {
          '__token__': that.data.info.__token__
        },
        success(res) {
          if (res.data) {
            let json = JSON.parse(res.data)
            if (json.success) {
              if (json.data) {
                app.globalData.appUserInfo = json.data
                that.setData({
                  avatar: json.data.avatar
                })
              }
            } else {
              if (json.data) {
                that.data.info.__token__ = json.data
              }
              if (json.message) {
                wx.showToast({
                  icon: 'none',
                  title: json.message,
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
          } else {
            wx.showToast({
              icon: 'none',
              title: '操作失败，系统异常',
              duration: 2000
            })
          }
        },
        complete() {
          wx.hideLoading()
        },
        fail(e) {
          wx.showToast({
            icon: 'none',
            title: '操作失败，系统异常',
            duration: 2000
          })
        }
      })
    } catch(e) {
      wx.hideLoading()
      Dialog.alert({
        title: '发生错误',
        message: e.message
      })
    }
  }
})