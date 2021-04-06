// pages/web/web.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    title: '项目笔记',
    url: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this

    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })
    
    if (options.url) {
      that.setData({
        url: options.url
      })
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

  bindmessage: function(e) { 
    this.setData({
      title: e.detail.data[0].title
    })
  },

  // 转发事件
  onShareAppMessage(object) {
    let shareData = {
      title: this.data.title,
      path: '/pages/web/web?url=' + this.data.url
    }
    return shareData
  },

  onShareTimeline(object) {
    let shareData = {
      title: this.data.title,
      query: 'url=' + this.data.url
    }
    return shareData
  }
})