// pages/my/favorite/favorite.js
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    showCheckbox: false,
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    list: [],
    checked: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
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

  toggleCeckBox: function() {
    this.setData({
      showCheckbox: !this.data.showCheckbox
    })
  },

  checkChange: function(event) {
    this.setData({
      checked: event.detail
    })
  },

  selectItem: function(event) {
    const { index } = event.currentTarget.dataset
    if (this.data.showCheckbox) {
      const checkbox = this.selectComponent(`.checkboxes-${index}`)
      checkbox.toggle()
    } else {
      const item = this.data.list[index]
      if (item.unit_id) {
        wx.navigateTo({
          url: '../../unit/view/view?id=' + item.unit_id,
        })
      } else {
        wx.navigateTo({
          url: '../../building/view/view?id=' + item.building_id,
        })
      }
    }
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

    app.get('my/favorite?page=' + that.data.pageIndex, (res) => {
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