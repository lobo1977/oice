// pages/daily/view/view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  /**
   * 页面的初始数据
   */
  data: {
    isLoading: false,
    isPullDown: false,
    id: 0,
    date: '',
    info: {
      level: 0,
      owner_id: 0,
      title: '',        // 摘要
      summary: '',      // 详情
      start_time: '',   // 时间
      user_id: 0,
      username: '',
      mobile: '',
      avatar: '',
      company_name: '',
      customer_name: '',
      building_name: '',
      unit_name: '',
      allowEdit: false,
      allowDelete: false,
      allowReview: false
    }
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.data.id = options.id
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
    if (app.globalData.refreshDaily) {
      this.getInfo()
      app.globalData.refreshDaily = false
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
  },

  onRemove: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这条日报吗？',
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
    
    let url = 'daily/detail?id=' + that.data.id
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          info: res.data
        })

        if (res.data.start_time) {
          let startTime = Date.parse(res.data.start_time.replace(/-/g, '/'))
          that.data.date = app.formatTime(startTime, 'yyyy-MM-dd')
          let start_time = app.formatTime(startTime, 'yyyy-MM-dd HH:mm')

          if (res.data.end_time) {
            start_time += ' 至 ' + app.formatTime(Date.parse(res.data.end_time.replace(/-/g, '/')), 'HH:mm')
          }
          that.setData({
            ['info.start_time']: start_time
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
      url: '../edit/edit?id=' + this.data.id
    })
  },
  
  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('daily/remove', {
      id: that.data.id
    }, (res) => {
      if (res.success) {
        app.globalData.refreshDaily = true
        app.goBack()
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

  review: function() {
    wx.navigateTo({
      url: '../review/review?user=' + this.data.info.user_id + '&date=' + this.data.date,
    })
  }
})