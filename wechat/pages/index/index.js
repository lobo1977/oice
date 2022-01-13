//index.js
//获取应用实例
const app = getApp()

Page({
  data: {
    activeTab: -1,
    isLoading: false,
    isPullDown: false,
    city: '',
    banner: [],
    article: [],
    unitList: []
  },

  //事件处理函数
  onLoad: function (options) {
    if (typeof this.getTabBar === 'function' && this.getTabBar()) {
      this.getTabBar().setData({
        selected: 0
      })
    }
    this.setData({
      city: app.globalData.currentCity
    })
    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })
    this.getData()
  },

  onShow: function() {
    if (app.globalData.changeCity) {
      this.setData({
        city: app.globalData.currentCity
      })
      app.globalData.changeCity = false
      this.getData()
    }
  },

  // 触发下拉刷新时执行
  onPullDownRefresh: function() {
    if (this.data.isLoading == false) {
      this.setData({
        isPullDown: true
      })
      this.getData()
    }
    wx.stopPullDownRefresh()
  },

  bindViewBuilding: function(event) {
    wx.navigateTo({
      url: '../building/view/view?id=' + event.currentTarget.dataset.id
    })
  },

  bindAddBuilding: function() {
    app.checkUser('/pages/building/edit/edit')
  },

  bindAddCustomer: function() {
    app.checkUser('/pages/customer/edit/edit')
  },

  switchCity: function() {
    wx.navigateTo({
      url: '../city/city'
    })
  },

  onTabChange(event) {
    this.setData({
      activeTab: event.detail.name
    })
    this.getArticle()
  },

  bindViewArticle: function(event) {
    console.log(event.currentTarget.dataset.url)
    wx.navigateTo({
      url: '../web/web?url=' + app.globalData.serverUrl + event.currentTarget.dataset.url
    })
  },

  // 通过接口获取数据
  getData: function() {
    let that = this

    if (that.data.isLoading) return

    that.setData({
      isLoading: true
    })

    that.setData({
      banner: [],
      article: [],
      unitList: []
    })

    app.post('index/index2', { 
      city: that.data.city
     }, (res) => {
      if (res.data) {
        that.setData({
          banner: res.data.banner,
          article: res.data.article,
          unitList: res.data.unit
        })
      }
    }, () => {
      that.setData({
        isPullDown: false,
        isLoading: false
      })
    })
  },

  getArticle: function() {
    let that = this

    if (that.data.isLoading) return

    that.setData({
      isLoading: true
    })

    that.setData({
      article: []
    })

    app.get('index/article?type=' + that.data.activeTab, (res) => {
      if (res.data) {
        that.setData({
          article: res.data
        })
      }
    }, () => {
      that.setData({
        isLoading: false
      })
    })
  }
})
