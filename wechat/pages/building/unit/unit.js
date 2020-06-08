//view.js

//获取应用实例
const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    info: {
      title: '',
      building_name: '',
      building_id: 0,
      building_no: '',
      floor: 0,          // 楼层
      room: '',          // 房间号
      face: '',          // 朝向
      acreage: 0,        // 面积
      rent_sell: '',     // 租售
      rent_price: 0,     // 出租价格
      sell_price: 0,     // 出售价格
      decoration: '',    // 装修状况
      status: 0,         // 状态
      statusText: '',
      end_date_text: '', // 到日期
      rem: '',           // 备注
      key: '',
      isFavorite: false,
      allowNew: false,
      allowEdit: false,
      allowDelete: false,
      images: [],
      linkman: []
    },
    previewImages: []
  },
  
  onLoad(options) {
    let that = this;
    if (options.id) {
      that.data.info.id = options.id
    }
    if (options.key) {
      that.data.info.key = options.key
    }
    if (app.globalData.appUserInfo) {
      that.getView()
    } else {
      app.userLoginCallback = () => {
        that.getView()
      }
    }
  },
  
  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
    this.data.isPullDown = false
  },
  
  // 转发事件
  onShareAppMessage(object) {
    let data = this.data.info
    let shareData = {
      title: data.building_name + ' ' + data.title,
      path: '/pages/unit/view/view?id=' + data.id + '&key=' + data.key
    }
    if (this.data.previewImages.length) {
      shareData.imageUrl = this.data.previewImages[0]
    }
    return shareData
  },
  
  // 预览图片
  bindViewImage(event) {
    let url = app.globalData.serverUrl + '/' + event.currentTarget.dataset.data.src
    wx.previewImage({
      current: url,
      urls: this.data.previewImages
    })
  },
  
  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data.mobile || data.tel) {
      wx.makePhoneCall({
        phoneNumber: data.mobile || data.tel
      })
    }
  },
  
  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    wx.setClipboardData({
      data: data.weixin || data.mobile
    })
  },
  
  // 获取数据
  getView: function() {
    let that = this;
    that.setData({
      isLoading: true,
    })
    
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    }
    
    let url = 'unit/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          info: res.data
        })
        let prevList = that.data.previewImages
        if (res.data.images.length) {
          for (let i = 0; i < res.data.images.length; i++) {
            prevList.push(app.globalData.serverUrl + '/' + res.data.images[i].src)
          }
          that.setData({
            previewImages: prevList
          })
        }
      }
    }, () => {
      that.setData({
        isLoading: false,
      })
      wx.hideLoading()
    })
  }
})