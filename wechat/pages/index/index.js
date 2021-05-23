//index.js
//获取应用实例
const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    banner: [],
    unitList: []
  },

  //事件处理函数
  onLoad: function () {
    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })
    this.getData()
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

  // 通过接口获取数据
  getData: function() {
    let that = this

    if (that.data.isLoading) return

    that.setData({
      isLoading: true
    })

    that.setData({
      banner: [],
      unitList: []
    })

    app.get('index/index', (res) => {
      if (res.data) {
        that.setData({
          banner: res.data.banner,
          unitList: res.data.unit
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
