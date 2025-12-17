#!/bin/bash

# 設定目標 URL (請確認你的 Docker Port 是 8080)
URL="http://localhost:8080/v1/order"

# 請求總數
TOTAL_REQUESTS=500

echo "🚀 [Start] 發送 $TOTAL_REQUESTS 個請求至 Gateway..."
echo "-----------------------------------------------------"

START_TIME=$(date +%s%N)

for i in $(seq 1 $TOTAL_REQUESTS)
do
   # 產生隨機資料
   USER_ID=$((1000 + i))
   
   # 發送請求 (安靜模式，只抓 HTTP Code)
   HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$URL" \
     -H "Content-Type: application/json" \
     -d "{\"user_id\": $USER_ID, \"product_id\": 5566, \"amount\": 1, \"note\": \"LoadTest-$i\"}")

   # 顯示進度
   if [ "$HTTP_CODE" -eq 201 ] || [ "$HTTP_CODE" -eq 202 ]; then
       echo -ne "✅ Req $i: 202 Accepted (Queued) \r"
   else
       echo -e "\n❌ Req $i Failed: HTTP $HTTP_CODE"
   fi
done

END_TIME=$(date +%s%N)
DURATION=$((($END_TIME - $START_TIME)/1000000))

echo -e "\n-----------------------------------------------------"
echo "🎉 發送完畢！"
echo "⏱️  Publisher (Gateway) 總耗時: ${DURATION} ms"
echo "👉 現在請檢查 Worker Log，看 Consumer 是否正在後台慢慢處理..."