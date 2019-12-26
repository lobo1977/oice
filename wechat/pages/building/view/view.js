//view.js

//获取应用实例
const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    info: {
      id: 0,
      building_name: '',    // 名称
      building_type: '',    // 类型
      area: '',             // 城区
      district: '',         // 商圈
      address: '',          // 地址
      longitude: 0,         // 经度
      latitude: 0,          // 纬度
      subway: '',           // 地铁
      completion_date_text: '',  // 竣工日期
      rent_sell: '',        // 租售
      price: '',            // 价格
      acreage: 0,           // 建筑面积
      floor: '',            // 楼层
      floor_area: 0,        // 层面积
      floor_height: 0,      // 层高
      bearing: 0,           // 楼板承重
      developer: '',        // 开发商
      manager: '',          // 物业管理
      fee: '',              // 物业费
      electricity_fee: '',  // 电费
      car_seat: '',         // 停车位
      rem: '',              // 项目说明
      facility: '',         // 配套设施
      equipment: '',        // 楼宇设备
      traffic: '',          // 交通状况
      environment: '',      // 周边环境
      user_id: 0,
      short_url: '',
      key: '',
      isFavorite: false,
      allowEdit: false,
      allowDelete: false,
      images: [],
      linkman: [],
      unit: [],
      confirm: []
    },
    previewImages: [],
    showUnits: []
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
      title: data.building_name,
      path: '/pages/building/view/view?id=' + data.id + '&key=' + data.key
    }
    if (this.data.previewImages.length) {
      shareData.imageUrl = this.data.previewImages[0]
    }
    return shareData
  },
  
  // 预览图片
  bindViewImage(event) {
    let url = app.serverUrl + '/' + event.currentTarget.dataset.data.src
    wx.previewImage({
      current: url,
      urls: this.data.previewImages
    })
  },
  
  // 打开地图
  bindOpenLocation() {
    let info = this.data.info
    wx.openLocation({
      latitude: info.latitude,
      longitude: info.longitude,
      scale: 18,
      name: info.building_name,
      address: info.area + info.address
    })
  },
  
  bindShowUnit(event) {
    let id = event.currentTarget.dataset.data.id
    wx.navigateTo({
      url: '../unit/unit?id=' + id
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
      isLoading: true
    })
    
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    }
    
    let url = 'building/detail?id=' + that.data.info.id
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
            prevList.push(app.serverUrl + '/' + res.data.images[i].src)
          }
          that.setData({
            previewImages: prevList
          })
        }
        if (res.data.unit.length) {
          that.setData({
            showUnits: res.data.unit.filter(function (unit) {
              return unit.status === 1
            })
          })
        }
      }
    }, () => {
      that.setData({
        isLoading: false
      })
      wx.hideLoading()
    })
  }
})