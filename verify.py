import math

def ann_pv(A,i,n): return A*(1-(1+i)**-n)/i
def ann_pay(P,i,n): return P/((1-(1+i)**-n)/i)
def ann_fv(A,i,n): return A*((1+i)**n-1)/i
def ann_payf(F,i,n): return F/(((1+i)**n-1)/i)

ok = lambda got,exp,tol=1.0: "OK " if abs(got-exp)<=tol else "FALLA"
r=[]

# --- Conversión de tasas (cap. 4) ---
def to_ea(value, kind, per_year, ref_year=1.0):
    m = per_year/ref_year
    if kind=='ef': i=value
    elif kind=='nv': i=value/m
    elif kind=='pa': i=value/(1-value)
    elif kind=='na': ia=value/m; i=ia/(1-ia)
    return (1+i)**per_year-1

def convert(value,kind,per_year,tgt_kind,tgt_per,ref=1.0,tgt_ref=1.0):
    ea=to_ea(value,kind,per_year,ref)
    i=(1+ea)**(1/tgt_per)-1
    m=tgt_per/tgt_ref
    if tgt_kind=='ef': return i
    if tgt_kind=='nv': return i*m
    if tgt_kind=='pa': return i/(1+i)
    if tgt_kind=='na': return (i/(1+i))*m

r.append(("4.2 20% CB -> EA", to_ea(0.20,'nv',6), 0.2174, 0.0001))
r.append(("4.3 35% EA -> NT", convert(0.35,'ef',1,'nv',4), 0.3116, 0.0001))
r.append(("4.6 36% CT -> NBim", convert(0.36,'nv',4,'nv',6), 0.3548, 0.0001))
r.append(("4.14 36% CTA -> NBim v", convert(0.36,'na',4,'nv',6), 0.3893, 0.0001))
r.append(("4.17 15% S -> TA", convert(0.15,'ef',2,'pa',4), 0.0675, 0.0001))
r.append(("4.8 18% S -> bimensual", convert(0.18,'ef',2,'ef',24), 0.01389, 0.00001))

# --- Amortización (cap. 7) ---
r.append(("7.1 cuota 600k 8% 8t", ann_pay(600000,0.08,8), 104408.86, 0.01))

extras={3:80000,5:100000}
ep=sum(a*(1+0.08)**-p for p,a in extras.items())
r.append(("7.2 cuota con extras", ann_pay(600000-ep,0.08,8), 81514.62, 0.01))

# 7.6 saldo periodo 79 y desglose pago 80
A=ann_pay(35000000,0.02,120)
s79=35000000*(1.02)**79 - ann_fv(A,0.02,79)
r.append(("7.6 cuota", A, 771683.39, 0.01))
r.append(("7.6 saldo n=79", s79, 21452404.48, 1.0))
r.append(("7.6 interes pago 80", s79*0.02, 429048.09, 1.0))
r.append(("7.6 abono pago 80", A-s79*0.02, 342635.30, 1.0))

# 7.4 gracia muerta
bal=10000000*(1.05)**3
r.append(("7.4 saldo tras gracia", bal, 11576250.00, 0.01))
r.append(("7.4 cuota", ann_pay(bal,0.05,6), 2280723.47, 0.01))

# 7.5 gracia cuota reducida
r.append(("7.5 cuota", ann_pay(10000000,0.05,6), 1970174.68, 0.01))

# 7.7 / 7.8 abono constante
r.append(("7.7 cuota 1", 2500000+20000000*0.075, 4000000.00, 0.01))
r.append(("7.7 cuota 8", 2500000+2500000*0.075, 2687500.00, 0.01))
r.append(("7.8 interes 0", 12000000*0.18, 2160000.00, 0.01))
r.append(("7.8 cuota 1", 1500000+10500000*0.18, 3390000.00, 0.01))

# --- Periodos fraccionarios (5.15) ---
F,A3,i3=17450260,430230,0.03
n=math.log(1+F*i3/A3)/math.log(1+i3)
r.append(("5.15 n exacto", n, 26.9317, 0.001))
r.append(("5.15 redondeo abajo", ann_payf(F,i3,26), 452629.91, 1.0))
r.append(("5.15 redondeo arriba", ann_payf(F,i3,27), 428651.86, 1.0))
acc=ann_fv(A3,i3,26)*(1+i3)
r.append(("5.15 cuota reducida", F-acc, 365984.37, 1.0))
r.append(("5.15 cuota extra", (F-ann_fv(A3,i3,26)*(1+i3))/(1+i3), 355324.63, 1.0))

# --- Ecuación de valor 4.37 ---
d1=200000*1.15**1; d2=150000*1.07**(16/3); d3=220000*1.03**20
i=0.02; ff=14
known=(d1*(1+i)**(ff-6)+d2*(1+i)**(ff-16)+d3*(1+i)**(ff-20))-300000*(1+i)**(ff-0)
r.append(("4.37 X mes 14", known, 433294.19, 1.0))

# --- Ejercicio 4.51 ---
i=0.015
coef=(1+i)**-4+(1+i)**-7
const=1500000-200000*(1+i)**-7
X=const/coef
r.append(("4.51 primer pago", X, 716030.14, 1.0))
r.append(("4.51 ultimo pago", X+200000, 916030.14, 1.0))

# --- Gradientes (cap. 6) ---
def ag_pv(A,G,i,n):
    a=(1-(1+i)**-n)/i
    return A*a+G*(a-n*(1+i)**-n)/i
def gg_pv(k,j,i,n):
    return k*(1-((1+j)/(1+i))**n)/(i-j)

r.append(("6.1 torno", ag_pv(220000,30000,0.035,18), 5901028.16, 1.0))
r.append(("6.20 obligacion", gg_pv(850000,0.10,0.03,24), 46694334.68, 1.0))
# 6.28 decreciente geométrico: j negativo
k=20000000/gg_pv(1,-0.018,0.02,15)
r.append(("6.28 primera cuota", k, 1750378.52, 1.0))
bal=20000000*1.02**9 - k*((1.02**9-(1-0.018)**9)/(0.02+0.018))
r.append(("6.28 saldo tras 9", bal, 7968548.86, 1.0))

print(f"{'caso':32} {'calculado':>18} {'libro':>18}  estado")
print("-"*95)
fails=0
for name,got,exp,tol in r:
    st=ok(got,exp,tol)
    if st=="FALLA": fails+=1
    print(f"{name:32} {got:18.4f} {exp:18.4f}  {st}")
print("-"*95)
print(f"{len(r)-fails} de {len(r)} coinciden con el texto")
