#!/usr/bin/env python
# coding: utf-8
#

import traceback
import mysql

def main():
    try:
        sql = "delete from ot_robot"
        mysql.execute_sql(sql)
        sql = "delete from ot_robot_group"
        mysql.execute_sql(sql)

        print ("[INFO] Clear boot finished.")
    except Exception as e:
        print e.message, traceback.format_exc()
        
if __name__ == '__main__':
    main()