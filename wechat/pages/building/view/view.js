//view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

//获取应用实例
const app = getApp()

Page({
  data: {
    me: null,
    isLoading: false,
    isPullDown: false,
    isVideoPlay: false,
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
      user: '',
      avatar: '',
      create_time_text: '',
      short_url: '',
      share: 0,
      status : 0,
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
    showAudit: false,
    auditSummary: '',
    auditError: false
  },
  
  onLoad(options) {
    let that = this
    app.globalData.refreshBuildingView = false
    
    if (options.id) {
      that.data.info.id = options.id
    }
    if (options.key) {
      that.data.info.key = options.key
    }
    if (app.globalData.appUserInfo) {
      this.setData({
        me: app.globalData.appUserInfo
      })
      that.getView()
    } else {
      app.userLoginCallback = () => {
        this.setData({
          me: app.globalData.appUserInfo
        })
        that.getView()
      }
    }
  },

  onShow: function () {
    if (app.globalData.refreshBuildingView) {
      this.getView()
      app.globalData.refreshBuildingView = false
    }
  },
  
  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
  },
  
  // 转发事件
  onShareAppMessage(object) {
    let data = this.data.info
    let shareData = {
      title: data.building_name,
      path: '/pages/building/view/view?id=' + data.id + '&key=' + data.key
    }
    if (this.data.info.images.length) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + this.data.info.images[0].msrc
    }
    return shareData
  },

  onVideoPlay() {
    this.setData({
      isVideoPlay: true
    })
  },

  onVideoStop() {
    this.setData({
      isVideoPlay: false
    })
  },
  
  // 预览图片
  bindViewImage(event) {
    let idx = event.currentTarget.dataset.data
    wx.previewMedia({
      sources: this.data.previewImages,
      current: idx
    })
  },
  
  // 打开地图
  bindOpenLocation() {
    let info = this.data.info
    if (info.latitude !== null && info.latitude !== 0 && info.longitude !== null && info.longitude !== 0) {
      let latLng = app.convertBD09ToGCJ02(info.latitude, info.longitude)
      wx.openLocation({
        latitude: latLng.lat,
        longitude: latLng.lng,
        scale: 18,
        name: info.building_name,
        address: info.area + info.address
      })
    }
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

  bindViewNote: function() {
    wx.navigateTo({
      url: '../../web/web?url=' + app.globalData.serverUrl + '/index/building/' + this.data.info.id
    })
  },

  bindEdit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindCopy: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id + '&copy=1'
    })
  },

  bindAddUnit: function() {
    wx.navigateTo({
      url: '../../unit/edit/edit?bid=' + this.data.info.id
    })
  },

  bindAddLinkman: function() {
    wx.navigateTo({
      url: '../../linkman/edit/edit?type=building&oid=' + this.data.info.id
    })
  },

  bindAudit: function() {
    this.setData({
      auditSummary: '',
      auditError: false,
      showAudit: true
    })
  },

  auditSummaryChange: function(event) {
    this.data.auditSummary = event.detail
    if (event.detail.length > 0 && this.data.auditError) {
      this.setData({
        auditError: false
      })
    }
  },

  onAuditClose: function(event) {
    if (event.detail == 'cancel') {
      if (this.data.auditSummary.length == 0) {
        this.setData({
          showAudit: false
        })
        this.setData({
          auditError: true,
          showAudit: true
        })
      } else {
        this.setData({
          auditError: false
        })
        this.doAudit(2)
      }
    } else if (event.detail == 'confirm') {
      this.data.auditSummary = ''
      this.setData({
        auditError: false
      })
      this.doAudit(1)
    } else {
      this.setData({
        showAudit: false
      })
    }
  },

  doAudit: function(flag) {
    let that = this
    that.setData({
      showAudit: false
    })
    wx.showLoading()
    app.post('building/audit', {
      id: that.data.info.id,
      status: flag,
      summary: that.data.auditSummary
    }, (res) => {
      if (res.success) {
        app.globalData.refreshBuilding = true
        wx.navigateBack()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },
  
  // 获取数据
  getView: function() {
    let that = this
    that.data.isLoading = true
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'building/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.success && res.data) {
        let prevList = []
        if (res.data.videos.length) {
          res.data.videos.forEach(element => {
            prevList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'video',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        if (res.data.images.length) {
          res.data.images.forEach(element => {
            prevList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'image',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        that.setData({
          info: res.data,
          previewImages: prevList
        })
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          wx.navigateBack()
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  },

  // 收藏
  favorite: function() {
    let that = this
    let url = that.data.info.isFavorite ? 'building/unFavorite' : 'building/favorite'
    wx.showLoading()
    app.post(url, {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        wx.showToast({
          icon: 'none',
          title: that.data.info.isFavorite ? '已取消收藏' : '已加入收藏',
          duration: 1000
        })
        that.setData({
          ['info.isFavorite']: that.data.info.isFavorite ? false : true
        })
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  }
})