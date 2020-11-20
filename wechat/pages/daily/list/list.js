// pages/daily/list/list.js
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    date: Date.now(),
    date_str: '',
    date_text: '',
    minDate: Date.now() - 180*24*60*60*1000,
    maxDate: Date.now() + 180*24*60*60*1000,
    showCalendar: false,
    type: 'list',
    id: 0,
    me: null,
    user: {
      title: '',
      superior_id: 0
    },
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isReviewLoading: false,
    isPullDown: false,
    isEnd: false,
    list: [],
    review: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.setData({
        id: options.id
      })
    }
    if (options.date) {
      that.setData({
        date: Date.parse(options.date.replace(/-/g, '/'))
      })
    }
    that.reSetDate()

    if (app.globalData.appUserInfo) {
      that.setData({
        me: app.globalData.appUserInfo
      })
      that.getUserInfo()
      that.getList()
      that.getReview()
    } else {
      app.userLoginCallback = () => {
        that.setData({
          me: app.globalData.appUserInfo
        })
        that.getUserInfo()
        that.getList()
        that.getReview()
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
      this.data.pageIndex = 1
      this.getList()
      this.getReview()
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
    if (this.data.isLoading == false) {
      this.setData({
        isPullDown: true
      })
      this.getUserInfo()
      this.data.pageIndex = 1
      this.getList()
      this.getReview()
    }
    wx.stopPullDownRefresh()
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    if (this.data.isEnd == false) {
      if (that.data.type = 'list') {
        this.data.pageIndex++
        this.getList()
      }
    }
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  },

  reSetDate: function() {
    let that = this
    const today = new Date()
    const xDate = new Date(that.data.date)
    let fmt = 'M月d日'
    if (today.getFullYear() != xDate.getFullYear()) {
      fmt = 'yyyy年M月d日'
    } else if (today.getMonth() == xDate.getMonth() && today.getDay() == xDate.getDay()) {
      fmt = 'M月d日（今日）'
    }
    that.setData({
      date_str: app.formatTime(that.data.date, 'yyyy-MM-dd'),
      date_text: app.formatTime(that.data.date, fmt),
      showCalendar: false
    })
  },

  goPrev: function() {
    if (this.data.date - 24*60*60*1000 < this.data.minDate) {
      return
    }
    this.setData({
      date: this.data.date - 24*60*60*1000
    })
    this.reSetDate()
    this.data.pageIndex = 1
    this.getList()
    this.getReview()
  },

  goNext: function() {
    if (this.data.date + 24*60*60*1000 > this.data.maxDate) {
      return
    }
    this.setData({
      date: this.data.date + 24*60*60*1000
    })
    this.reSetDate()
    this.data.pageIndex = 1
    this.getList()
    this.getReview()
  },

  openCalendar: function() {
    this.setData({
      showCalendar: true
    })
  },

  onCalendarClose: function() {
    this.setData({
      showCalendar: false
    })
  },

  onCalendarConfirm: function(event) {
    this.setData({
      date: event.detail.getTime()
    })
    this.reSetDate()
    this.data.pageIndex = 1
    this.getList()
    this.getReview()
  },

  add: function() {
    wx.navigateTo({
      url: '../edit/edit',
    })
  },

  review: function() {
    wx.navigateTo({
      url: '../review/review?user=' + this.data.id + '&date=' + this.data.date_str,
    })
  },

  onTabChange(event) {
    this.setData({
      type: event.detail.name
    })
  },

  getUserInfo: function() {
    let that = this
    app.post('user/detail', { 
      id: that.data.id
    }, (res) => {
      if (res.data) {
        that.setData({
          user: res.data
        })
        wx.setNavigationBarTitle({
          title: (that.data.me.id == res.data.id ? '我' : res.data.title) + '的工作日报'
        })
      }
    }, () => {
    })
  },

  // 获取列表
  getList: function() {
    let that = this

    if (that.data.isLoading) return

    that.setData({
      isLoading: true
    })

    if (that.data.pageIndex <= 1) {
      that.setData({
        list: []
      })
    }

    app.post('daily/user', { 
      id: that.data.id,
      page: that.data.pageIndex,
      date: that.data.date_str
    }, (res) => {
      if (!res.data || res.data.length < that.data.pageSize) {
        that.setData({
          isEnd: true
        })
      } else {
        that.setData({
          isEnd: false
        })
      }
      if (res.data) {
        let list = that.data.list.concat(res.data)
        that.setData({
          list: list
        })
      }
    }, () => {
      that.setData({
        isPullDown: false,
        isLoading: false
      })
    })
  },

  // 批阅列表
  getReview: function() {
    let that = this
    that.setData({
      isReviewLoading: true
    })
    app.post('daily/review', { 
      id: that.data.id,
      date: that.data.date_str
    }, (res) => {
      if (res.data) {
        that.setData({
          review: res.data
        })
      }
    }, () => {
      that.setData({
        isReviewLoading: false
      })
    })
  }
})