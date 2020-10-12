// pages/company/view/view.js
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    isLoading: false,
    isPullDown: false,
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

  onRemove: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个企业吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    });
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