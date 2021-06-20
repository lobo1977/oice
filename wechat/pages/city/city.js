var city = require('../../utils/city.js');

//获取应用实例
const app = getApp()

Page({
  data: {
    city,
    cityWord: ["A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "W", "X", "Y", "Z"],
    hidden: true,
    trans: "0",
    cityName: "", //获取选中的城市名
    winHeight: 0,
    lineHeight: 0,
    endWord: '',
    showWord: '',
    scrollTopId: ''
  },

  onLoad: function (options) {
    // 生命周期函数--监听页面加载
  },

  onReady: function () {
    // 生命周期函数--监听页面初次渲染完成
    let that = this
    let lineHeight = (app.globalData.windowHeight - 150) / 22;
    that.setData({
      winHeight: app.globalData.windowHeight - 40,
      lineHeight: lineHeight
    })
  },

  onShow: function () {
    // 生命周期函数--监听页面显示
  },

  onHide: function () {
    // 生命周期函数--监听页面隐藏
  },

  onUnload: function () {
    // 生命周期函数--监听页面卸载
  },

  //触发全部开始选择
  chStart: function () {
    this.setData({
      trans: ".3",
      hidden: false
    })
  },

  //触发结束选择
  chEnd: function () {
    this.setData({
      trans: "0",
      hidden: true,
      scrollTopId: this.data.endWord
    })
  },

  // 滑动选择城市
  chMove: function (e) {
    var y = e.touches[0].clientY
    var offsettop = e.currentTarget.offsetTop
    var that = this

    //判断选择区域,只有在选择区才会生效
    if (y > offsettop && y < app.globalData.windowHeight - 10) {
      var num = parseInt((y - offsettop) / that.data.lineHeight);
      that.setData({
        endWord: that.data.cityWord[num],
        showWord: that.data.cityWord[num]
      })
    }
  },

  //获取文字信息
  getWord: function (e) {
    var id = e.target.id
    this.setData({
      showWord: id,
      endWord: id
    })
  },

  //设置文字信息
  setWord: function (e) {
    var id = e.target.id
    this.setData({
      scrollTopId: id
    })
  },

  //选择城市，并让选中的值显示在文本框里
  bindCity: function (e) {
    let cityName = e.currentTarget.dataset.city
    this.setData({
      cityName: cityName
    })
    app.globalData.currentCity = cityName
    app.globalData.changeCity = true
    wx.navigateBack()
  }
})