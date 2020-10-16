// pages/company/view/view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    isLoading: false,
    isPullDown: false,
    showInvite: false,
    inviteMobile: '',
    info: {
      id: 0,
      title: '',
      full_name: '',
      logo: '',
      stamp: '',
      stampView: '',
      enable_stamp: 1,
      area: '',
      address: '',
      rem: '',
      join_way: 0,
      addin: 0,
      wait: 0,
      isAddin: false,
      isInvtie: false,
      allowEdit: false,
      allowInvite: false,
      allowPass: false,
      allowDelete: false
    },
    waitUser: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.data.info.id = options.id
    }

    if (app.globalData.appUserInfo) {
      this.getInfo()
    } else {
      app.userLoginCallback = () => {
        this.getInfo()
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
    if (app.refreshCompany) {
      this.getInfo()
      app.refreshCompany = false
    }
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
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getInfo()
    }
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
    let data = this.data.info
    let shareData = {
      title: data.full_name,
      path: '/pages/company/view/view?id=' + data.id
    }
    if (data.logo) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + data.logo
    }
    return shareData
  },

  onInvite: function() {
    this.setData({
      showInvite: true,
      inviteMobile: ''
    })
  },

  closeInvite: function() {
    this.setData({
      showInvite: false,
      inviteMobile: ''
    })
  },

  inviteMobileInput: function(event) {
    this.data.inviteMobile = event.detail.value
  },

  invite: function() {
    let that = this
    let dlg = this.selectComponent('#dlgInvite')
    if (that.data.inviteMobile == '') {
      dlg.stopLoading()
      return
    } else if (!app.isMobile(that.data.inviteMobile)) {
      dlg.stopLoading()
      wx.showToast({
        title: '请输入有效的手机号码',
        icon: 'none',
        duration: 1500
      })
      return
    } else {
      app.post('company/invite', {
        id: that.data.info.id,
        mobile: that.data.inviteMobile
      }, (res) => {
        if (res.success) {
          Dialog.alert({
            title: '提示',
            message: '您向手机号 ' + that.data.inviteMobile + ' 的用户发出邀请，请等待对方确认。'
          })
        } else {
          Dialog.alert({
            title: '发生错误',
            message: res.message ? res.message : '系统异常'
          })
        }
      }, () => {
        that.setData({
          showInvite: false
        })
      })
    }
  },

  onRemove: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个企业吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    })
  },

  getInfo: function() {
    let that = this
    that.data.isLoading = true

    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'company/detail?id=' + that.data.info.id
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          info: res.data
        })
        if (res.data.waitUser) {
          that.setData({
            waitUser: res.data.waitUser
          })
        }
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  },

  addin: function() {
    let that = this
    wx.showLoading()
    app.post('company/addin', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        app.updateUserInfo()
        if (res.data == 1) {
          Dialog.alert({
            title: '恭喜',
            message: '您已加入' + that.data.info.title
          }).then(() => {
            wx.navigateBack()
          })
        } else {
          Dialog.alert({
            title: '提示',
            message: '您已申请加入' + that.data.info.title + '，请等待企业管理员审核。'
          }).then(() => {
            wx.navigateBack()
          })
        }
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  quit:function() {
    let that = this
    Dialog.confirm({
      title: '退出确认',
      message: '您确定要退出' + that.data.info.title + '吗？',
    })
    .then(() => {
      wx.showLoading()
      app.post('company/quit', {
        id: that.data.info.id
      }, (res) => {
        if (res.success) {
          if (res.data) {
            app.globalData.appUserInfo = res.data
          }
          app.updateUserInfo()
          Dialog.alert({
            title: '提示',
            message: '您已退出' + that.data.info.title
          }).then(() => {
            wx.navigateBack()
          })
        } else {
          Dialog.alert({
            title: '发生错误',
            message: res.message ? res.message : '系统异常'
          })
        }
      }, () => {
        wx.hideLoading()
      })
    })
    .catch(() => {
    })
  },

  pass: function(event) {
    const user_id = event.currentTarget.dataset.id
    this.audit(user_id, 'company/passAddin')
  },

  reject: function(event) {
    const user_id = event.currentTarget.dataset.id
    this.audit(user_id, 'company/rejectAddin')
  },

  audit: function(user_id, url) {
    let that = this
    wx.showLoading()
    app.post(url, {
      id: that.data.info.id,
      user_id: user_id
    }, (res) => {
      if (res.success) {
        that.getInfo()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  edit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },
  
  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('company/remove', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        wx.navigateBack()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  }
})