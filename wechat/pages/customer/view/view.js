// pages/customer/view/view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    activeTab: 0,
    isLoading: false,
    isPullDown: false,
    steps: [
      {
        text: '潜在'
      },
      {
        text: '跟进'
      },
      {
        text: '看房'
      },
      {
        text: '洽谈'
      },
      {
        text: '成交'
      },
    ],
    logs: [],
    previewImages: [],
    activeStep: 0,
    activeIcon: 'checked',
    activeColor: '#07c160',
    info: {
      id: 0,
      customer_name: '',    // 名称
      tel: '',              // 直线电话
      area: '',             // 城区
      address: '',          // 地址
      demand: '',           // 需求项目
      lease_buy: '',        // 租赁/购买
      district: '',         // 意向商圈
      acreage: '',          // 面积
      budget: '',           // 预算
      settle_date: '',      // 入驻日期
      current_area: '',     // 在驻面积
      end_date: '',         // 到日期
      rem: '',              // 备注
      status: 0,            // 状态
      clash: 0,             // 撞单
      user_id: 0,
      company_id: 0,
      manager: '',          // 客户经理
      avatar: '',
      manager_mobile: '',
      company: '',          // 所属企业
      key: '',
      isShare: false,
      allowEdit: false,
      allowTurn: false,
      allowFollow: false,
      allowConfirm: false,
      allowClash: false,
      allowDelete: false,
      linkman: [],            // 联系人
      log: [],                // 跟进纪要
      attach: [],             // 附件
      filter: [],             // 拼盘
      recommend: [],          // 推荐资料
      confirm: [],            // 确认书
      clashCustomer: null,
      shareList: []
    },
    tapStartTime: 0,
    tapEndTime: 0,
    showShareActions:false,
    shareIndex: 0,
    shareActions: [
      {
        name: '移除',
      }
    ],
    showAttachActions: false,
    attachIndex: 0,
    attachActions: [
      {
        name: '删除',
      }
    ],
    showTurn: false,
    searching: false,
    keyword: '',
    userList: [],
    showFilter: false,
    isFilterLoading: false,
    filterPage: 1,
    filterPageSize: 10,
    isFilterEnd: false,
    filterList: [],
    filterResult: [],
    showFilterCheckbox: false,
    filterCheckAll: false,
    filterChecked: [],
    showFilterActions: false,
    filterIndex: 0,
    filterActions: [
      {
        name: '选择',
      },
      {
        name: '删除',
      }
    ],
    showRecommendActions: false,
    recommendIndex: 0,
    recommendActions: [
      {
        name: '删除',
      }
    ],
    showRecommendType: false,
    recommendType: [ 
      {name: '手机版', value: 0}, 
      {name: '打印版', value: 1}, 
      {name: '打印版（中英对照）', value: 2}, 
      {name: '打印版（横版）', value: 3}, 
      {name: '对比表', value: 4}
    ],
    showClashMenu: false,
    clashWay: [
      {
        name: '强行转交',
        color: '#07c160',
        value: 0
      },
      {
        name: '并行处理',
        value: 1
      },
      {
        name: '驳回',
        color: '#ee0a24',
        value: 2
      }
    ]
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
    
    app.globalData.refreshCustomerView = false

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

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    if (app.globalData.refreshCustomerView) {
      this.getView()
      app.globalData.refreshCustomerView = false
    }
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
      this.data.isPullDown = true
      this.getView()
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
    let data = this.data.info
    let shareData = {
      title: data.customer_name,
      path: '/pages/customer/view/view?id=' + data.id + '&key=' + data.key
    }
    return shareData
  },

  onShareTimeline: function() {
    let data = this.data.info
    return {
      title: data.customer_name,
      query: 'id=' + data.id + '&key=' + data.key
    }
  },

  onTabChange: function(event) {
    this.setData({
      activeTab: event.detail.index
    })
  },

  bindTouchStart: function(e) {
    this.tapStartTime = e.timeStamp;
  },

  bindTouchEnd: function(e) {
    this.tapEndTime = e.timeStamp;
  },

  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data) {
      wx.makePhoneCall({
        phoneNumber: data
      })
    }
  },

  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    if (data) {
      wx.setClipboardData({
        data: data
      })
    }
  },

  bindViewContact: function(event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let contact = this.data.info.shareList[event.currentTarget.dataset.data]
    wx.navigateTo({
      url: '../../contact/view/view?id=' + contact.id
    })
  },

  bindViewAttach: function (event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let attach = this.data.info.attach[event.currentTarget.dataset.data]
    let url = app.globalData.serverUrl + '/' + attach.url
    if (attach.is_image) {
      wx.previewImage({
        current: url,
        urls: this.data.previewImages
      })
    } else {
      wx.showLoading({title: '加载中'})
      wx.downloadFile({
        url: url,
        success: function (res) {
          if (res.statusCode === 200) {
            wx.openDocument({
              showMenu: true,
              filePath: res.tempFilePath,
              success: function (res) {
              }
            })
          }
        },
        complete: function() {
          wx.hideLoading()
        }
      })
    }
  },

  bindViewBuilding: function (event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let item = event.currentTarget.dataset.data
    if (item.unit_id) {
      wx.navigateTo({
        url: '../../unit/view/view?id=' + item.unit_id
      })
    } else {
      wx.navigateTo({
        url: '../../building/view/view?id=' + item.building_id
      })
    }
  },

  bindAddLinkman: function() {
    wx.navigateTo({
      url: '../../linkman/edit/edit?type=customer&oid=' + this.data.info.id
    })
  },

  bindAddLog: function(event) {
    wx.navigateTo({
      url: '../log/log?oid=' + this.data.info.id
    })
  },

  bindUpload: function(event) {
    let that = this
    let count = 0
    let error = 0

    app.checkSystem('/app/customer/view/' + that.data.info.id + '?tab=2')

    wx.chooseMessageFile({
      count: 5,
      success(res) {
        wx.showLoading({title: '上传中'})
        try {
          res.tempFiles.forEach(element => {
            wx.uploadFile({
              header: {
                'Content-Type': 'multipart/form-data',
                'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
                  app.globalData.appUserInfo.token : ''
              },
              url: app.globalData.serverUrl + '/api/customer/uploadAttach',
              filePath: element.url,
              name: 'attach[]',
              formData: {
                'id': that.data.info.id,
                'name': element.name
              },
              success (res2) {
                if (res2.data) {
                  let json = JSON.parse(res2.data)
                  if (json.success) {
                    that.setData({
                      ['info.attach']: json.data
                    })
                    that.setPrevImages(json.data)
                  } else {
                    error++
                  }
                } else {
                  error++
                }
              },
              complete() {
                count++
                if (count >= res.tempFiles.length) {
                  wx.hideLoading()
                  if (error > 0) {
                    Dialog.alert({
                      title: '发生错误',
                      message: error + '个文件上传失败'
                    })
                  }
                }
              }, 
              fail(e) {
                error++
                console.log(e.errMsg)
              }
            })
          })
        } catch(e) {
          Dialog.alert({
            title: '发生错误',
            message: e.message
          }).then(() => {
          })
        }
      }
    })
  },

  bindUnShare: function(event) {
    let that = this
    Dialog.confirm({
      title: '取消确认',
      message: '确定不再查看这个客户了吗？',
    }).then(() => {
      that.unShare(app.globalData.appUserInfo.id)
    }).catch(() => {
    })
  },

  bindShareLongTap: function(event) {
    let idx = event.currentTarget.dataset.data
    if (this.data.info.allowEdit) {
      this.setData({
        showShareActions: true,
        shareIndex: idx
      })
    }
  },

  onShareActionsClose: function() {
    this.setData({
      showShareActions: false
    })
  },

  onShareActionsSelect: function(event) {
    let that = this
    if (event.detail.name == '移除') {
      let idx = that.data.shareIndex
      that.unShare(that.data.info.shareList[idx].id, idx)
    }
  },

  bindAttachLongTap: function(event) {
    let idx = event.currentTarget.dataset.data
    if (this.data.info.allowFollow && app.globalData.appUserInfo.id == this.data.info.attach[idx].user_id) {
      this.setData({
        showAttachActions: true,
        attachIndex: idx
      })
    }
  },

  bindFilterLongTap: function(event) {
    let idx = event.currentTarget.dataset.index
    if (this.data.info.allowFollow) {
      this.setData({
        showFilterActions: true,
        filterIndex: idx
      })
    }
  },

  bindRecommendLongTap: function(event) {
    let idx = event.currentTarget.dataset.index
    if (this.data.info.allowFollow) {
      this.setData({
        showRecommendActions: true,
        recommendIndex: idx
      })
    }
  },

  onAttachActionsClose: function() {
    this.setData({
      showAttachActions: false
    })
  },

  onAttachActionsSelect: function(event) {
    let that = this
    if (event.detail.name == '删除') {
      wx.showLoading()
      app.post('customer/removeAttach', {
        attach_id: that.data.info.attach[that.data.attachIndex].id,
      }, (res) => {
        if (res.success) {
          that.data.info.attach.splice(that.data.attachIndex, 1)
          that.setData({
            ['info.attach']: that.data.info.attach
          })
        } else {
          wx.showToast({
            icon: 'none',
            title: res.message ? res.message : '操作失败，系统异常',
            duration: 2000
          })
        }
      }, () => {
        wx.hideLoading()
      })
    }
  },

  onFilterActionsClose: function() {
    this.setData({
      showFilterActions: false
    })
  },

  onFilterActionsSelect: function(event) {
    let that = this
    if (event.detail.name == '删除') {
      that.removeFilter(that.data.filterIndex)
    } else if (event.detail.name == '选择') {
      let arr = that.data.filterChecked
      arr.push(that.data.filterIndex.toString())
      this.setData({
        showFilterCheckbox: true,
        filterChecked: arr
      })
    }
  },

  onRecommendActionsClose: function() {
    this.setData({
      showRecommendActions: false
    })
  },

  onRecommendActionsSelect: function(event) {
    let that = this
    if (event.detail.name == '删除') {
      that.removeRecommend(that.data.filterIndex)
    }
  },

  bindEdit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindDelete: function(event) {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个客户吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    })
  },

  bindTurn: function() {
    this.setData({
      userList: [],
      keyword: '',
      showTurn: true
    })
    this.searchUser('')
  },

  onTurnClose: function() {
    this.setData({
      showTurn: false
    })
  },

  onKewordChange: function(event) {
    this.data.keyword = event.detail
  },

  bindSearch: function() {
    this.searchUser()
  },

  bindSelectTurnUser: function(event) {
    let that = this
    let newUser = event.currentTarget.dataset.data
    //if (newUser.id === that.data.info.user_id) return
    Dialog.confirm({
      title: '转交确认',
      message: '确定要将客户转交给【' + newUser.title + '】吗？',
    }).then(() => {
      that.setData({
        showTurn: false
      })
      that.turn(newUser)
    }).catch(() => {
    })
  },

  bindAddFilter: function() {
    this.data.filterPage = 1
    this.setData({
      filterList: [],
      filterResult: [],
      showFilter: true
    })
    this.getFilter()
  },

  onFilterClose: function() {
    this.setData({
      showFilter: false
    })
  },

  bindFilterClick: function(event) {
    const idx = event.currentTarget.dataset.index
    const checkbox = this.selectComponent(`.filter-${idx}`)
    checkbox.toggle()
  },

  onFilterChange: function(event) {
    this.setData({
      filterResult: event.detail
    })
  },

  onFilterConfirm: function() {
    let that = this
    if (that.data.filterResult.length == 0) {
      wx.showToast({
        title: '请选择要加入拼盘的项目',
        icon: 'none',
        duration: 2000
      })
      return
    }

    that.setData({
      showFilter: false
    })

    wx.showLoading()

    let bids = ''
    let uids = ''
    that.data.filterResult.forEach(element => {
      let arr = element.split(',')
      if (arr[0]) {
        if (bids) {
          bids += ','
        }
        bids += arr[0]
      }
      if (arr[1] != '' && arr[1] != '0') {
        if (uids) {
          uids += ','
        }
        uids += arr[1]
      }
    })
    app.post('customer/addFilter', {
      cid: that.data.info.id,
      bids: bids,
      uids: uids
    }, (res) => {
      if (res.success) {
        that.getView()
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

  setPrevImages: function(files) {
    if (files.length) {
      for (let i = 0; i < files.length; i++) {
        if (files[i].is_image) {
          this.data.previewImages.push(app.globalData.serverUrl + '/' + files[i].src)
        }
      }
    }
  },

  noop: function() {},

  batchRemmoveFilter: function(event) {
    let that = this
    let count = 0
    wx.showLoading()
    this.data.filterChecked.forEach((item) => {
      count ++
      let idx = Number(item)
      let obj = that.data.info.filter[idx]
      obj.deleted = true
      app.post('customer/removeFilter', {
        id: that.data.info.id,
        building_id: obj.building_id,
        unit_id: obj.unit_id
      }, (res) => {
        if (res.success) {
        } else {
          // Dialog.alert({
          //   title: '发生错误',
          //   message: res.message ? res.message : '系统异常'
          // })
        }
      }, () => {
        if (count >= that.data.filterChecked.length) {
          that.data.info.filter.forEach((item, index, arr) => {
            if (item.deleted) {
              arr.splice(index, 1)
            }
          })
          that.setData({
            ['info.filter']: that.data.info.filter,
            filterCheckAll: false,
            filterChecked: [],
            showFilterCheckbox: that.data.info.filter.length
          })
          wx.hideLoading()
        }
      })
    })
  },

  removeFilter: function(idx) {
    let that = this
    const filter = that.data.info.filter[idx]
    wx.showLoading()
    app.post('customer/removeFilter', {
      id: that.data.info.id,
      building_id: filter.building_id,
      unit_id: filter.unit_id
    }, (res) => {
      if (res.success) {
        that.data.info.filter.splice(idx, 1)
        that.setData({
          ['info.filter']: that.data.info.filter
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
  },

  bindRemoveFilter : function(event) {
    const idx = event.currentTarget.dataset.index
    this.removeFilter(idx)
  },

  // 获取数据
  getView: function() {
    let that = this
    that.data.isLoading = true

    if (that.data.isPullDown == false) {
      wx.showLoading({title: '加载中'})
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'customer/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.success && res.data) {
        let arrSteps = that.data.steps
        let step = res.data.status
        let stepIcon = "checked"
        let stepColor = "#07c160"
        let logs = []

        arrSteps[4].text = '成交'
        if (step > 5) {
          step = 0
        } else if (step == 5) {
          step = 4
          arrSteps[4].text = '失败'
          stepIcon = "clear"
          stepColor = "#ff0000"
        }

        if (res.data.log && res.data.log.length) {
          for (let i in res.data.log) {
            logs.push({
              text: res.data.log[i].title,
              desc: res.data.log[i].summary.replace(/<br\/>/g, '\n')
                + '\n' + res.data.log[i].user + ' ' + res.data.log[i].time_span
            })
          }
        }

        that.setData({
          info: res.data,
          steps: arrSteps,
          activeStep: step,
          activeIcon: stepIcon,
          activeColor: stepColor,
          logs: logs
        })

        that.setPrevImages(res.data.attach)
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          app.goBack()
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  },

  searchUser: function() {
    let that = this
    that.setData({
      searching:true
    })
    app.post('user/search', {
      company: app.globalData.appUserInfo.company_id,
      keyword: that.data.keyword,
    }, (res) => {
      if (res.success && res.data) {
        that.setData({
          userList: res.data
        })
      } else {
        that.setData({
          userList: []
        })
      }
    }, () => {
      that.setData({
        searching:false
      })
    })
  },

  turn: function(newUser) {
    let that = this
    wx.showLoading()
    app.post('customer/turn', {
      id: that.data.info.id,
      user_id: newUser.id,
      company_id: newUser.company_id
    }, (res) => {
      if (res.success) {
        app.getMyCustomer()
        app.globalData.refreshCustomer = true
        Dialog.alert({
          message: '转交成功',
        }).then(() => {
          app.goBack()
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
  },

  getMoreFilter: function() {
    if (!this.data.isFilterEnd) {
      this.data.filterPage++
      this.getFilter()
    }
  },

  getFilter: function() {
    let that = this

    if (that.data.isFilterLoading) 
      return

    that.setData({
      isFilterLoading: true
    })
    app.get('my/favorite?page=' + that.data.filterPage + '&page_size=' + that.data.filterPageSize, (res) => {
      if (res.success) {
        if (res.data && res.data.length) {
          let list = that.data.filterList.concat(res.data)
          that.setData({
            filterList: list,
            isFilterEnd: res.data.length < that.data.filterPageSize
          })
        } else {
          that.setData({
            isFilterEnd: true
          })
        }
      }
    }, () => {
      that.setData({
        isFilterLoading: false
      })
    })
  },

  toggleFilterCheckbox: function() {
    this.setData({
      showFilterCheckbox: !this.data.showFilterCheckbox,
      filterChecked: []
    })
  },

  filterCheckChange: function(event) {
    this.setData({
      filterChecked: event.detail
    })
  },

  toggleFilterCheckAll: function(event) {
    this.setData({
      filterCheckAll: event.detail,
    })
    if (!this.data.filterCheckAll) {
      this.setData({
        filterChecked: [],
      })
    } else {
      let arr = []
      for (let i = 0; i < this.data.info.filter.length; i++) {
        arr.push(i.toString())
      }
      this.setData({
        filterChecked: arr,
      })
    }
  },

  unShare: function(user_id, idx) {
    let that = this
    wx.showLoading()
    app.post('customer/removeShare', {
      id: that.data.info.id,
      user_id: user_id,
    }, (res) => {
      if (res.success) {
        if (idx || idx === 0) {
          that.data.info.shareList.splice(idx, 1)
          that.setData({
            ['info.shareList']: that.data.info.shareList
          })
        } else {
          app.globalData.refreshCustomer = true
          app.goBack()
        }
      } else {
        wx.showToast({
          icon: 'none',
          title: res.message ? res.message : '操作失败，系统异常',
          duration: 2000
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('customer/remove', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        app.getMyCustomer()
        app.globalData.refreshCustomer = true
        app.goBack()
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

  toRecommend: function() {
    // this.setData({
    //   showRecommendType: true
    // })
    this.onRecommendTypeSelect()
  },

  onRecommendTypeClose: function() {
    this.setData({
      showRecommendType: false
    })
  },

  getRecommendData: function(mode) {
    let data = {}
    let i = 0
    data['cid'] = this.data.info.id
    data['mode'] = mode
    this.data.filterChecked.forEach((item) => {
      let obj = this.data.info.filter[Number(item)]
      data['ids[' + i + ']'] = obj.building_id + ',' + obj.unit_id
      i++
    })
    return data
  },

  onRecommendTypeSelect: function(event) {
    let that = this
    let mode = 0
    if (event) {
      mode = event.detail.value
    }
    wx.showLoading()
    app.post('customer/recommend', 
      that.getRecommendData(mode), (res) => {
      if (res.success) {
        Dialog.alert({
          message: '已成功生成推荐资料'
        }).then(() => {
          that.setData({
            activeTab: 4,
            filterCheckAll: false,
            filterChecked: [],
            ['info.recommend']: res.data
          })
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
  },

  viewRecommend: function(event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let item = event.currentTarget.dataset.data
    //if (item.mode == 0) {
      wx.navigateTo({
        url: '../../contact/recommend/recommend?id=' + item.token
      })
    // } else {
    //   wx.showLoading({title: '加载中'})
    //   wx.downloadFile({
    //     url: app.globalData.serverUrl + '/index/print/' + item.token + '/' + item.mode,
    //     success: function (res) {
    //       if (res.statusCode === 200) {
    //         wx.openDocument({
    //           showMenu: true,
    //           fileType: 'pdf',
    //           filePath: res.tempFilePath,
    //           success: function (res) {
    //           }
    //         })
    //       }
    //     },
    //     complete: function() {
    //       wx.hideLoading()
    //     }
    //   })
    //}
  },

  removeRecommend: function(idx) {
    let that = this
    Dialog.confirm({
      title: '删除资料',
      message: '删除后，已分享给客户的资料将无法查看，确定要删除吗？',
    })
    .then(() => {
      wx.showLoading()
      const item = that.data.info.recommend[idx]
      app.post('customer/removeRecommend', {
        id: item.id
      }, (res) => {
        if (res.success) {
          that.data.info.recommend.splice(idx, 1)
          that.setData({
            ['info.recommend']: that.data.info.recommend
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
    })
    .catch(() => {
    })
  },

  bindRemoveRecommend : function(event) {
    const idx = event.currentTarget.dataset.index
    this.removeRecommend(idx)
  },

  auditClash: function() {
    this.setData({
      showClashMenu: true
    })
  },

  onClashMenuClose: function() {
    this.setData({
      showClashMenu: false
    })
  },

  clashPass: function(operate) {
    let that = this
    wx.showLoading()
    app.post('customer/clashPass', {
      id: that.info.data.id,
      operate: operate
    }, (res) => {
      if (res.success) {
        Dialog.alert({
          message: '撞单处理完成'
        }).then(() => {
          if (operate === 2) {
            app.getMyCustomer()
            app.globalData.refreshCustomer = true
            app.goBack()
          } else {
            that.getView()
          }
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
  },

  onClashMenuSelect: function(event) {
    let that = this
    that.setData({
      showClashMenu: false
    })
    if (event.detail.value === 2) {
      Dialog.confirm({
        title: '驳回确认',
        message: '当前客户信息将被删除，确定要驳回处理吗？',
      }).then(() => {
        that.clashPass(event.detail.value)
      })
    } else if (event.detail.value === 0) {
      Dialog.confirm({
        title: '强行转交确认',
        message: '被撞单客户将转交给当前用户，当前客户信息将被删除，确定要强行转交吗？',
      }).then(() => {
        that.clashPass(event.detail.value)
      })
    } else {
      that.clashPass(event.detail.value)
    }
  }
})