// pages/company/index/index.js
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    isLoading: false,
    isPullDown: false,
    keyword: '',
    showSearch: false,
    me: null,
    searchResult: [],
    my: [],
    waites: [],
    inviteMe: [],
    creates: [],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (app.globalData.appUserInfo) {
      this.setData({
        me: app.globalData.appUserInfo
      })
      this.getList()
    } else {
      app.userLoginCallback = () => {
        this.setData({
          me: app.globalData.appUserInfo
        })
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
    this.setData({
      showSearch: false
    })
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
      this.setData({
        isPullDown: true
      })
      this.getList()
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

  onKewordChange(event) {
    this.data.keyword = event.detail
  },

  onSearch: function(event) {
    let that = this
    if (that.data.keyword == '') {
      return
    }
    that.setData({
      showSearch: true
    })
    app.post('company/search', {
      keyword: that.data.keyword
    }, (res) => {
      if (res.success && res.data) {
        that.setData({
          searchResult: res.data
        })
      } else {
        that.setData({
          searchResult: []
        })
      }
    }, () => {
    })
  },

  onCancelSearch: function() {
    this.setData({
      keyword: '',
      showSearch: false
    })
  },

  onCloseSearch: function() {
    this.setData({
      showSearch: false
    })
  },

  getList: function() {
    let that = this
    that.setData({
      isLoading: true
    })
    
    app.get('company', (res) => {
      if (res.data) {
        if (res.data.my) {
          that.setData({
            my: res.data.my
          })
        } else {
          that.setData({
            my: []
          })
        }
        if (res.data.myWait) {
          that.setData({
            waites: res.data.myWait
          })
        } else {
          that.setData({
            waites: []
          })
        }
        if (res.data.inviteMe) {
          that.setData({
            inviteMe: res.data.inviteMe
          })
        } else {
          that.setData({
            inviteMe: []
          })
        }
        if (res.data.myCreate) {
          that.setData({
            creates: res.data.myCreate
          })
        } else {
          that.setData({
            creates: []
          })
        }
      }
    }, () => {
      wx.hideLoading()
      that.setData({
        isPullDown: false,
        isLoading: false
      })
    })
  },

  setActiveCompany(id) {
    if (this.data.me.company_id == id) {
      return
    }

    wx.showLoading({
      title: '切换中'
    })

    app.post('company/setActive', {
      id: id
    }, (res) => {
      if (res.success) {
        this.setData({
          ['me.company_id']: id,
        })
        app.globalData.appUserInfo.company_id = id
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
  },

  newCompany: function() {
    wx.navigateTo({
      url: '../edit/edit',
    })
  },

  changeCompany: function(event) {
    this.setActiveCompany(event.detail)
  },

  onCompanyClick: function(event) {
    const { name } = event.currentTarget.dataset
    this.setActiveCompany(name)
  }
})