// pages/customer/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    id: 0,
    area: [],
    showArea: false,
    demand:[],
    showDemand: false,
    lease_buy: [ {name: '租赁'}, {name: '购买'}],
    showLeaseBuy: false,
    district: [],
    showDistrict: false,
    showSettleDate: false,
    numberSettleDate: Date.now(),
    showEndDate: false,
    numberEndDate: Date.now(),
    status: [ 
      {name: '潜在', value: 0}, 
      {name: '跟进', value: 1}, 
      {name: '看房', value: 2}, 
      {name: '洽谈', value: 3}, 
      {name: '成交', value: 4}, 
      {name: '失败', value: 5}, 
      {name: '名录', value: 6}
    ],
    showStatus: false,
    info: {
      __token__: '',
      customer_name: '',    // 名称
      tel: '',              // 直线电话
      area: '',             // 城区
      address: '',          // 地址
      demand: '',           // 需求项目
      lease_buy: '',        // 租赁/购买
      district: '',         // 意向商圈
      min_acreage: null,    // 最小面积(平方米)
      max_acreage: null,    // 最大面积(平方米)
      budget: '',           // 预算
      settle_date: '',      // 入驻日期
      current_area: null,   // 在驻面积
      end_date: '',         // 到期日
      remind: 8,            // 到期提醒（提前月份数）
      rem: '',              // 项目说明
      status: 6,            // 状态
      company_id: 0,        // 所属企业
      share: 0,
      linkman: '',          // 联系人
      clash: 0              // 撞单客户
    },
    is_name_empty: false,
    name_error: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.setData({
        id: options.id
      })
    }
    if (app.globalData.appUserInfo) {
      that.getData()
    } else {
      app.userLoginCallback = () => {
        that.getData()
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

  // 获取数据
  getData: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'customer/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        for (let item in that.data.info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            that.setData({
              ['info.' + item]: res.data[item]
            })
          }
        }
        if (res.data.settle_date) {
          that.setData({
            ['info.settle_date']: app.formatTime(Date.parse(res.data.settle_date.replace(/-/g, '/')), 'yyyy-MM-dd'),
            numberSettleDate: Date.parse(res.data.settle_date.replace(/-/g, '/'))
          })
        } else {
          that.setData({
            ['info.settle_date']: ''
          })
        }
        if (res.data.end_date) {
          that.setData({
            ['info.end_date']: app.formatTime(Date.parse(res.data.end_date.replace(/-/g, '/')), 'yyyy-MM-dd'),
            numberEndDate: Date.parse(res.data.end_date.replace(/-/g, '/'))
          })
        } else {
          that.setData({
            ['info.end_date']: ''
          })
        }
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          wx.navigateBack()
        })
      }
    }, () => {
      let arrArea = []
      let arrDistrict = []
      app.globalData.area.forEach(element => {
        if (element.id != 'all') {
          arrArea.push(element.text)
          if (element.children && element.children.length) {
            let dataDistrict = that.data.info.district ? that.data.info.district : ''
            element.children.forEach(d => {
              if (d.id != '') {
                arrDistrict.push( { text: d.text, type: dataDistrict.indexOf(d.text) >= 0 ? 'success' : 'default' })
              }
            })
          }
        }
      })
      let arrDemand = []
      app.globalData.buildingType.forEach(element => {
        if (element.text != '类别') {
          arrDemand.push(element.text)
        }
      })
      that.setData({
        area: arrArea,
        demand: arrDemand,
        district: arrDistrict
      })
      wx.hideLoading()
    })
  },

  onNameInput: function(event) {
    this.setData({
      ['info.customer_name']: event.detail
    })
  },

  onTelInput: function(event) {
    this.setData({
      ['info.tel']: event.detail
    })
  },

  onLinkmanInput: function(event) {
    this.setData({
      ['info.linkman']: event.detail
    })
  },

  onAddressInput: function(event) {
    this.setData({
      ['info.address']: event.detail
    })
  },

  bindSelectArea: function() {
    this.setData({
      showArea: true
    })
  },

  onAreaPickerClose: function() {
    this.setData({
      showArea: false
    })
  },

  onAreaSelected: function(event) {
    this.setData({
      ['info.area']: event.detail.value,
      showArea: false
    })
  },

  bindSelectDemand: function() {
    this.setData({
      showDemand: true
    })
  },

  onDemandPickerClose: function() {
    this.setData({
      showDemand: false
    })
  },

  onDemandSelected: function(event) {
    this.setData({
      ['info.demand']: event.detail.value,
      showDemand: false
    })
  },

  bindSelectLeaseBuy: function() {
    this.setData({
      showLeaseBuy: true
    })
  },

  onLeaseBuyClose: function() {
    this.setData({
      showLeaseBuy: false
    })
  },

  onLeaseBuySelect: function(event) {
    this.setData({
      ['info.lease_buy']: event.detail.name,
      showLeaseBuy: false
    })
  },

  bindSelectDistrict: function() {
    this.setData({
      showDistrict: true
    })
  },

  onDistrictClose: function() {
    let strDistrict = ''
    this.data.district.forEach(d => {
      if (d.type == 'success') {
        if (strDistrict.length) {
          strDistrict += ','
        }
        strDistrict+= d.text
      }
    })
    this.setData({
      showDistrict: false,
      ['info.district']: strDistrict
    })
  },

  onDistrictTap: function(event) {
    let idx = event.target.dataset.data
    let arrDistrict = this.data.district
    if (arrDistrict[idx].type == 'default') {
      arrDistrict[idx].type = 'success'
    } else {
      arrDistrict[idx].type = 'default'
    }
    this.setData({
      district: arrDistrict
    })
  },

  onMinAcreageInput: function(event) {
    this.setData({
      ['info.min_acreage']: event.detail
    })
  },

  onMaxAcreageInput: function(event) {
    this.setData({
      ['info.max_acreage']: event.detail
    })
  },

  onBudgeInput: function(event) {
    this.setData({
      ['info.budge']: event.detail
    })
  },

  onCurrentAreaInput: function(event) {
    this.setData({
      ['info.current_area']: event.detail
    })
  },

  bindSelectSettleDate: function() {
    this.setData({
      showSettleDate: true
    })
  },

  onSettleDateConfirm: function(event) {
    this.setData({
      ['info.settle_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showSettleDate: false
    })
  },

  onSettleDateClose: function() {
    this.setData({
      showSettleDate: false
    })
  },

  bindSelectEndDate: function() {
    this.setData({
      showEndDate: true
    })
  },

  onEndDateConfirm: function(event) {
    this.setData({
      ['info.end_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showEndDate: false
    })
  },

  onEndDateClose: function() {
    this.setData({
      showEndDate: false
    })
  },

  onRemindChange: function(event) {
    this.setData({
      ['info.remind']: event.detail
    })
  },

  onRemInput: function(event) {
    this.setData({
      ['info.rem']: event.detail
    })
  },

  onShareChange: function(event) {
    this.setData({
      ['info.share']: event.detail ? 1 : 0
    })
  },

  bindSelectStatus: function() {
    this.setData({
      showStatus: true
    })
  },

  onStatusClose: function() {
    this.setData({
      showStatus: false
    })
  },

  onStatusSelect: function(event) {
    this.setData({
      ['info.status']: event.detail.value,
      showStatus: false
    })
  },

  bindSave: function() {
    let that = this
    if (!that.data.info.customer_name) {
      that.setData({
        is_name_empty: true,
        name_error: '请输入客户名称'
      })
    } else {
      that.setData({
        is_name_empty: false,
        name_error: ''
      })

      wx.showLoading({
        title: '保存中',
      })

      app.post('customer/edit?id=' + that.data.id, that.data.info, (res) => {
        if (res.success) {
          if (res.message) {
            Dialog.alert({
              title: '保存成功',
              message: res.message
            }).then(() => {
              if (that.data.id == 0) {
                let id = res.data
                wx.redirectTo({
                  url: '../view/view?id=' + id
                })
              } else {
                wx.navigateBack()
              }
            })
          } else if (that.data.id == 0) {
            let id = res.data
            wx.redirectTo({
              url: '../view/view?id=' + id
            })
          } else {
            wx.navigateBack()
          }
        } else if (res.data) {
          if (res.data.token) {
            that.data.info.__token__ = res.data.token
          }
          if (res.data.confirm && res.data.clash) {
            Dialog.confirm({
              title: '撞单提醒',
              message: res.message
            }).then(() => {
              that.data.info.clash = res.data.clash
              that.bindSave()
            })
          } else {
            Dialog.alert({
              title: res.data.clash ? '撞单提醒' : '发生错误',
              message: res.message
            }).then(() => {
              if (res.data.clash) {
                let id = res.data.clash
                wx.redirectTo({
                  url: '../view/view?id=' + id
                })
              }
            })
          }
        } else if (res.message) {
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
      }, () => {
        wx.hideLoading()
      })
    }
  }
})