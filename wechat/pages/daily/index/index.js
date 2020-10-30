// pages/daily/index/index.js
//获取应用实例
const app = getApp()

Page({
  /**
   * 页面的初始数据
   */
  data: {
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    date: '',
    list: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.data.date = app.formatTime(Date.now(), 'yyyy-MM-dd')
    if (app.globalData.appUserInfo) {
      this.getList()
    } else {
      app.userLoginCallback = () => {
        this.getList()
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
      this.getList()
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
      this.data.pageIndex = 1
      this.getList()
    }
    wx.stopPullDownRefresh()
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    if (this.data.isEnd == false) {
      this.data.pageIndex++
      this.getList()
    }
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  bindAdd: function() {
    wx.navigateTo({
      url: '../edit/edit',
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

    app.post('daily/index', { 
      id: that.data.id,
      page: that.data.pageIndex,
      date: that.data.date
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
  }
})