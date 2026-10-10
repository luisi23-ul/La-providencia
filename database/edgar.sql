--
-- PostgreSQL database dump
--

\restrict LALG3Fecua3ZtJwhdeKJLN7srORMdGaS07bqB1CjHh72qSgtIMb7qneKA5ijnze

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

-- Started on 2026-10-05 16:40:58

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 5046 (class 0 OID 17024)
-- Dependencies: 239
-- Data for Name: cables_manga; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cables_manga (id, manga_id, nombre, capacidad, sentido) FROM stdin;
\.


--
-- TOC entry 5040 (class 0 OID 16949)
-- Dependencies: 233
-- Data for Name: cajas_nap; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cajas_nap (id, codigo, ubicacion, nivel, tipo_splitter, capacidad_puertos, latitud, longitud, manga_origen_id, nap_alimentadora_id, created_at, numero_nap, hilo_manga, hilo_troncal) FROM stdin;
1	san francisco CC10	\N	nivel_1	1x8	8	10.496900	-66.853600	\N	\N	2026-10-04 13:50:36.539705	\N	\N	\N
2	san francisco cc101	\N	nivel_2	1x8	8	10.451580	11.545450	\N	1	2026-10-05 13:08:58.616622	\N	\N	\N
3	san francisco cc1011	\N	nivel_2	1x8	8	10.321000	11.325000	\N	1	2026-10-05 13:13:22.234304	\N	\N	\N
4	m011-207	\N	nivel_1	1x16	16	10.321000	11.254000	1	\N	2026-10-05 13:16:52.150735	\N	\N	\N
5	m-954	\N	nivel_2	1x8	8	10.254000	11.241000	\N	4	2026-10-05 13:16:52.162073	\N	\N	\N
6	terepaima	\N	nivel_1	1x8	8	10.212300	11.211400	1	\N	2026-10-05 13:25:10.096434	\N	\N	\N
7	terepaina-S1	\N	nivel_2	1x8	8	10.215400	11.214000	\N	6	2026-10-05 13:25:10.107131	\N	\N	\N
8	Agua Viva	\N	nivel_1	1x8	8	10.234500	11.543200	3	\N	2026-10-05 13:41:59.776074	\N	\N	\N
9	AguaViva-S1	\N	nivel_2	1x8	8	10.234500	11.543200	\N	8	2026-10-05 13:41:59.787035	\N	\N	\N
\.


--
-- TOC entry 5042 (class 0 OID 16978)
-- Dependencies: 235
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes (id, nombre_completo, identificacion, telefono, direccion, created_at) FROM stdin;
\.


--
-- TOC entry 5027 (class 0 OID 16580)
-- Dependencies: 220
-- Data for Name: fusiones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fusiones (id, manga_id, cable_in_id, hilo_in, cable_out_id, hilo_out, atenuacion_estimada_db, tubo_in, tubo_out, observacion) FROM stdin;
\.


--
-- TOC entry 5038 (class 0 OID 16928)
-- Dependencies: 231
-- Data for Name: mangas_empalme; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mangas_empalme (id, codigo, marca, zona_cobertura, capacidad_fusiones, latitud, longitud, detalles_hilos, created_at, direccion) FROM stdin;
1	m011-20	Huawei	barquisimeto	96	10.321000	11.325000	\N	2026-10-05 13:15:28.11208	\N
2	terepaima	Huawei	barquisimeto	144	10.215000	11.254400	\N	2026-10-05 13:28:19.88268	\N
3	AguaViva	Huawei	barquisimeto	96	10.234500	11.543200	\N	2026-10-05 13:37:30.130778	\N
\.


--
-- TOC entry 5036 (class 0 OID 16909)
-- Dependencies: 229
-- Data for Name: olts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.olts (id, nombre, marca, modelo, ip_gestion, latitud, longitud, total_puertos_pon, estado, created_at) FROM stdin;
1	Central Chacao CCS-01	Huawei	SmartAX MA5800	192.168.10.1	10.49690000	-66.85360000	16	activo	2026-10-03 16:14:19.338298
3	san francisco	Huawei	SmartAX MA5800	192.168.10.1	10.49690000	-66.85360000	560	activo	2026-10-03 19:04:24.593673
2	Central Valencia VAL-01	ZTE	ZXA10 C320	192.168.20.1	10.16210000	-68.00770000	800	inactivo	2026-10-03 16:14:19.338298
\.


--
-- TOC entry 5031 (class 0 OID 16856)
-- Dependencies: 224
-- Data for Name: permisos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permisos (id, codigo, descripcion) FROM stdin;
1	usuarios.crud	Crear, editar y suspender usuarios
2	olts.ver	Consultar inventario de OLTs
3	olts.crear	Registrar nuevas OLTs
4	olts.editar	Modificar configuraciones de OLTs
5	mangas.ver	Ver mangas de empalme
6	mangas.gestionar	Fusionar hilos y gestionar mangas
7	naps.ver	Consultar cajas NAP y splitters
8	naps.asignar_puertos	Asignar puertos de NAP a contratistas
9	cables.ver_todos	Visualizar infraestructura de cableado
10	cables.editar	Trazar y modificar rutas de fibra óptica
11	olts	Editar y registrar Nodos OLT
12	puertos	Gestionar puertos y cajas NAP
13	mangas	Gestionar mangas y fusiones
14	cableado	Modificar cableado principal
15	usuarios	Administrar usuarios del sistema
\.


--
-- TOC entry 5044 (class 0 OID 16992)
-- Dependencies: 237
-- Data for Name: puertos_nap; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.puertos_nap (id, caja_nap_id, numero_puerto, estado, potencia_optica, cliente_id, contratista_id, created_at) FROM stdin;
3	2	3	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
4	2	4	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
5	2	5	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
6	2	6	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
7	2	7	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
8	2	8	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
1	2	1	ocupado	-19.50	\N	\N	2026-10-05 13:08:58.616622
2	2	2	libre	-19.50	\N	\N	2026-10-05 13:08:58.616622
9	3	1	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
10	3	2	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
11	3	3	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
12	3	4	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
13	3	5	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
14	3	6	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
15	3	7	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
16	3	8	libre	-19.50	\N	\N	2026-10-05 13:13:22.234304
19	5	3	libre	-19.50	\N	\N	2026-10-05 13:16:52.162073
20	5	4	libre	-19.50	\N	\N	2026-10-05 13:16:52.162073
21	5	5	libre	-19.50	\N	\N	2026-10-05 13:16:52.162073
22	5	6	libre	-19.50	\N	\N	2026-10-05 13:16:52.162073
23	5	7	libre	-19.50	\N	\N	2026-10-05 13:16:52.162073
24	5	8	libre	-19.50	\N	\N	2026-10-05 13:16:52.162073
25	7	1	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
26	7	2	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
27	7	3	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
28	7	4	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
29	7	5	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
30	7	6	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
31	7	7	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
32	7	8	libre	-19.50	\N	\N	2026-10-05 13:25:10.107131
18	5	2	ocupado	-19.50	\N	\N	2026-10-05 13:16:52.162073
17	5	1	ocupado	-19.50	\N	\N	2026-10-05 13:16:52.162073
36	9	4	libre	-19.50	\N	\N	2026-10-05 13:41:59.787035
37	9	5	libre	-19.50	\N	\N	2026-10-05 13:41:59.787035
38	9	6	libre	-19.50	\N	\N	2026-10-05 13:41:59.787035
39	9	7	libre	-19.50	\N	\N	2026-10-05 13:41:59.787035
40	9	8	libre	-19.50	\N	\N	2026-10-05 13:41:59.787035
33	9	1	ocupado	-19.50	\N	\N	2026-10-05 13:41:59.787035
34	9	2	ocupado	-19.50	\N	\N	2026-10-05 13:41:59.787035
35	9	3	ocupado	-19.50	\N	5	2026-10-05 13:41:59.787035
\.


--
-- TOC entry 5029 (class 0 OID 16845)
-- Dependencies: 222
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, nombre) FROM stdin;
1	administrador
2	tecnico
3	contratista
\.


--
-- TOC entry 5034 (class 0 OID 16891)
-- Dependencies: 227
-- Data for Name: usuario_permisos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario_permisos (usuario_id, permiso_id) FROM stdin;
1	1
1	4
1	6
1	8
1	10
2	2
2	5
2	7
2	9
5	7
3	2
3	7
3	5
3	9
\.


--
-- TOC entry 5033 (class 0 OID 16868)
-- Dependencies: 226
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuarios (id, nombre, email, password_hash, telefono, rol_id, solo_lectura, activo, created_at) FROM stdin;
1	Super Administrador	admin@geonet.local	$2b$10$KredZM5r5znrq2r9l9Shde1.upnGjCNFSipervA17L20UKQvlVwuq	+58 412 7775214	1	f	t	2026-10-03 16:14:19.338298
4	ana	ana@gmail.com	$2b$10$XEcR3t8MVmDYvWe.DEPqk.63aAKi.vVdzWr9DgCjB2lVXYx85daXW	41245845	2	f	f	2026-10-04 16:20:41.151319
2	luisa	santeliz27@gmail.com	$2b$10$mNORqpNIIqUvvGgF/gFlUOxT3NfW1r79h8j.ZzIchbySf4nmXseQi	04127818865	2	t	t	2026-10-03 17:06:06.354856
5	Aixa Alejos	aixaaap2002@gmail.com	$2b$10$y2WzfCdILNRwJ7UM8fNyVOPGtqYL3EuiEfl9u0BVb6adCIkSjGAly	04145410171	2	t	t	2026-10-05 13:32:13.617519
3	carmelo	carmelo10@gmail.com	$2b$10$JCS2L5UP1fZ/.4I8MoK.7ecbrODMcZjnxcJxt9Hf16wnGAmV/SyQC	04245286234	3	t	t	2026-10-03 17:40:41.355286
\.


--
-- TOC entry 5062 (class 0 OID 0)
-- Dependencies: 238
-- Name: cables_manga_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cables_manga_id_seq', 1, false);


--
-- TOC entry 5063 (class 0 OID 0)
-- Dependencies: 232
-- Name: cajas_nap_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cajas_nap_id_seq', 9, true);


--
-- TOC entry 5064 (class 0 OID 0)
-- Dependencies: 234
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_id_seq', 1, false);


--
-- TOC entry 5065 (class 0 OID 0)
-- Dependencies: 219
-- Name: fusiones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fusiones_id_seq', 1, false);


--
-- TOC entry 5066 (class 0 OID 0)
-- Dependencies: 230
-- Name: mangas_empalme_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mangas_empalme_id_seq', 3, true);


--
-- TOC entry 5067 (class 0 OID 0)
-- Dependencies: 228
-- Name: olts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.olts_id_seq', 4, true);


--
-- TOC entry 5068 (class 0 OID 0)
-- Dependencies: 223
-- Name: permisos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permisos_id_seq', 15, true);


--
-- TOC entry 5069 (class 0 OID 0)
-- Dependencies: 236
-- Name: puertos_nap_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.puertos_nap_id_seq', 40, true);


--
-- TOC entry 5070 (class 0 OID 0)
-- Dependencies: 221
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 1, false);


--
-- TOC entry 5071 (class 0 OID 0)
-- Dependencies: 225
-- Name: usuarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_seq', 5, true);


-- Completed on 2026-10-05 16:40:59

--
-- PostgreSQL database dump complete
--

\unrestrict LALG3Fecua3ZtJwhdeKJLN7srORMdGaS07bqB1CjHh72qSgtIMb7qneKA5ijnze

