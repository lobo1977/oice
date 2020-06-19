// pages/customer/view/view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    goEdit: false,
    info: {
      id: 0,
      title: '',         // 名称
      department: '',    // 部门
      job: '',           // 职务
      mobile: '',        // 手机号码
      tel: '',           // 直线电话
      email: '',         // 电子邮箱
      weixin: '',        // 微信
      qq: '',            // QQ
      rem: '',           // 备注
      status: 0,
      allowEdit: false,
      allowDelete: false
    }
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this;
    if (options.id) {
      that.data.info.id = options.id
    }
    if (app.globalData.appUserInfo) {
      that.getView()
    } else {
      app.userLoginCallback = () => {
        that.getView()
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
    if (this.data.goEdit) {
      this.getView()
      this.data.goEdit = false
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
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
    this.data.isPullDown = false
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
      title: data.title,
      path: '/pages/linkman/view/view?id=' + data.id
    }
    return shareData
  },

  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data) {
      wx.makePhoneCall({
        phoneNumber: data
      })
    }
  },

  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    if (data) {
      wx.setClipboardData({
        data: data
      })
    }
  },

  bindEdit: function() {
    this.data.goEdit = true
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindRemove: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个联系人吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    });
  },

  // 获取数据
  getView: function() {
    let that = this;
    that.setData({
      isLoading: true
    })
    
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    }
    
    let url = 'linkman/detail?id=' + that.data.info.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        that.setData({
          info: res.data
        })
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          wx.navigateBack()
        })
      }
    }, () => {
      that.setData({
        isLoading: false
      })
      wx.hideLoading()
    })
  },

  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('linkman/remove', {
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