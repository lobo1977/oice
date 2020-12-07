// pages/customer/Log/log.js

const app = getApp()

Page({
  data: {
    id: 0,
    __token__: '',
    owner_id: 0,
    start_time: Date.now(),
    title: '',
    summary: '',
    showDateTimePicker: false,
    title_error: '',
    is_title_empty: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.data.id = options.id
      wx.setNavigationBarTitle({
        title: '修改跟进纪要'
      })
    } else {
      wx.setNavigationBarTitle({
        title: '添加跟进纪要'
      })
    }
    if (options.oid) {
      that.data.owner_id = options.oid
    }
    if (app.globalData.appUserInfo) {
      that.getData()
    } else {
      app.userLoginCallback = () => {
        that.getData()
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

  bindSelectTime: function() {
    this.setData({
      showDateTimePicker: true
    })
  },

  onDateTimePickerConfirm: function(event) {
    this.setData({
      start_time: event.detail,
      showDateTimePicker: false
    })
  },

  onDateTimePickerCancel: function() {
    this.setData({
      showDateTimePicker: false
    })
  },

  onDateTimePickerClose: function() {
    this.setData({
      showDateTimePicker: false
    })
  },

  onTitleInput: function(event) {
    this.data.title = event.detail
  },

  onSummaryInput: function(event) {
    this.data.summary = event.detail
  },

  // 获取数据
  getData: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })

    let url = 'customer/log?id=' + that.data.id
    app.get(url, (res) => {
      if (res.data) {
        that.data.__token__ = res.data.__token__
        if (res.data.owner_id) {
          that.data.owner_id = res.data.owner_id
        }
        that.setData({
          start_time: res.data.start_time ? Date.parse(res.data.start_time.replace(/-/g, '/')) : Date.now(),
          title: res.data.title || '',
          summary: res.data.summary || ''
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  bindSave: function() {
    let that = this
    if (!that.data.title) {
      that.setData({
        is_title_empty: true,
        title_error: '请输入摘要'
      })
    } else {
      that.setData({
        is_title_empty: false,
        title_error: ''
      })

      wx.showLoading({
        title: '保存中',
      })

      app.post('customer/log?id=' + that.data.id, {
        __token__: that.data.__token__,
        owner_id: that.data.owner_id,
        start_time: app.formatTime(that.data.start_time, 'yyyy-MM-dd HH:mm:ss'),
        title: that.data.title,
        summary: that.data.summary
      }, (res) => {
        if (res.success) {
          app.globalData.refreshCustomerView = true
          app.goBack()
        } else if (res.message) {
          wx.showToast({
            icon: 'none',
            title: res.message,
            duration: 2000
          })
        }
      }, () => {
        wx.hideLoading()
      })
    }
  }
})