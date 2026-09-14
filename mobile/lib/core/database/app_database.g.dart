// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_database.dart';

// ignore_for_file: type=lint
class $AppSettingsTable extends AppSettings
    with TableInfo<$AppSettingsTable, AppSetting> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $AppSettingsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _keyMeta = const VerificationMeta('key');
  @override
  late final GeneratedColumn<String> key = GeneratedColumn<String>(
    'key',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _valueMeta = const VerificationMeta('value');
  @override
  late final GeneratedColumn<String> value = GeneratedColumn<String>(
    'value',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [key, value];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'app_settings';
  @override
  VerificationContext validateIntegrity(
    Insertable<AppSetting> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('key')) {
      context.handle(
        _keyMeta,
        key.isAcceptableOrUnknown(data['key']!, _keyMeta),
      );
    } else if (isInserting) {
      context.missing(_keyMeta);
    }
    if (data.containsKey('value')) {
      context.handle(
        _valueMeta,
        value.isAcceptableOrUnknown(data['value']!, _valueMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {key};
  @override
  AppSetting map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return AppSetting(
      key: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}key'],
      )!,
      value: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}value'],
      ),
    );
  }

  @override
  $AppSettingsTable createAlias(String alias) {
    return $AppSettingsTable(attachedDatabase, alias);
  }
}

class AppSetting extends DataClass implements Insertable<AppSetting> {
  final String key;
  final String? value;
  const AppSetting({required this.key, this.value});
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['key'] = Variable<String>(key);
    if (!nullToAbsent || value != null) {
      map['value'] = Variable<String>(value);
    }
    return map;
  }

  AppSettingsCompanion toCompanion(bool nullToAbsent) {
    return AppSettingsCompanion(
      key: Value(key),
      value: value == null && nullToAbsent
          ? const Value.absent()
          : Value(value),
    );
  }

  factory AppSetting.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return AppSetting(
      key: serializer.fromJson<String>(json['key']),
      value: serializer.fromJson<String?>(json['value']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'key': serializer.toJson<String>(key),
      'value': serializer.toJson<String?>(value),
    };
  }

  AppSetting copyWith({
    String? key,
    Value<String?> value = const Value.absent(),
  }) => AppSetting(
    key: key ?? this.key,
    value: value.present ? value.value : this.value,
  );
  AppSetting copyWithCompanion(AppSettingsCompanion data) {
    return AppSetting(
      key: data.key.present ? data.key.value : this.key,
      value: data.value.present ? data.value.value : this.value,
    );
  }

  @override
  String toString() {
    return (StringBuffer('AppSetting(')
          ..write('key: $key, ')
          ..write('value: $value')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(key, value);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is AppSetting &&
          other.key == this.key &&
          other.value == this.value);
}

class AppSettingsCompanion extends UpdateCompanion<AppSetting> {
  final Value<String> key;
  final Value<String?> value;
  final Value<int> rowid;
  const AppSettingsCompanion({
    this.key = const Value.absent(),
    this.value = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  AppSettingsCompanion.insert({
    required String key,
    this.value = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : key = Value(key);
  static Insertable<AppSetting> custom({
    Expression<String>? key,
    Expression<String>? value,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (key != null) 'key': key,
      if (value != null) 'value': value,
      if (rowid != null) 'rowid': rowid,
    });
  }

  AppSettingsCompanion copyWith({
    Value<String>? key,
    Value<String?>? value,
    Value<int>? rowid,
  }) {
    return AppSettingsCompanion(
      key: key ?? this.key,
      value: value ?? this.value,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (key.present) {
      map['key'] = Variable<String>(key.value);
    }
    if (value.present) {
      map['value'] = Variable<String>(value.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('AppSettingsCompanion(')
          ..write('key: $key, ')
          ..write('value: $value, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $CachedHalaqasTable extends CachedHalaqas
    with TableInfo<$CachedHalaqasTable, CachedHalaqa> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CachedHalaqasTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _codeMeta = const VerificationMeta('code');
  @override
  late final GeneratedColumn<String> code = GeneratedColumn<String>(
    'code',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _centerIdMeta = const VerificationMeta(
    'centerId',
  );
  @override
  late final GeneratedColumn<int> centerId = GeneratedColumn<int>(
    'center_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _centerNameMeta = const VerificationMeta(
    'centerName',
  );
  @override
  late final GeneratedColumn<String> centerName = GeneratedColumn<String>(
    'center_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [id, name, code, centerId, centerName];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cached_halaqas';
  @override
  VerificationContext validateIntegrity(
    Insertable<CachedHalaqa> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('code')) {
      context.handle(
        _codeMeta,
        code.isAcceptableOrUnknown(data['code']!, _codeMeta),
      );
    }
    if (data.containsKey('center_id')) {
      context.handle(
        _centerIdMeta,
        centerId.isAcceptableOrUnknown(data['center_id']!, _centerIdMeta),
      );
    } else if (isInserting) {
      context.missing(_centerIdMeta);
    }
    if (data.containsKey('center_name')) {
      context.handle(
        _centerNameMeta,
        centerName.isAcceptableOrUnknown(data['center_name']!, _centerNameMeta),
      );
    } else if (isInserting) {
      context.missing(_centerNameMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  CachedHalaqa map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CachedHalaqa(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      code: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}code'],
      ),
      centerId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}center_id'],
      )!,
      centerName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}center_name'],
      )!,
    );
  }

  @override
  $CachedHalaqasTable createAlias(String alias) {
    return $CachedHalaqasTable(attachedDatabase, alias);
  }
}

class CachedHalaqa extends DataClass implements Insertable<CachedHalaqa> {
  final int id;
  final String name;
  final String? code;
  final int centerId;
  final String centerName;
  const CachedHalaqa({
    required this.id,
    required this.name,
    this.code,
    required this.centerId,
    required this.centerName,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['name'] = Variable<String>(name);
    if (!nullToAbsent || code != null) {
      map['code'] = Variable<String>(code);
    }
    map['center_id'] = Variable<int>(centerId);
    map['center_name'] = Variable<String>(centerName);
    return map;
  }

  CachedHalaqasCompanion toCompanion(bool nullToAbsent) {
    return CachedHalaqasCompanion(
      id: Value(id),
      name: Value(name),
      code: code == null && nullToAbsent ? const Value.absent() : Value(code),
      centerId: Value(centerId),
      centerName: Value(centerName),
    );
  }

  factory CachedHalaqa.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CachedHalaqa(
      id: serializer.fromJson<int>(json['id']),
      name: serializer.fromJson<String>(json['name']),
      code: serializer.fromJson<String?>(json['code']),
      centerId: serializer.fromJson<int>(json['centerId']),
      centerName: serializer.fromJson<String>(json['centerName']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'name': serializer.toJson<String>(name),
      'code': serializer.toJson<String?>(code),
      'centerId': serializer.toJson<int>(centerId),
      'centerName': serializer.toJson<String>(centerName),
    };
  }

  CachedHalaqa copyWith({
    int? id,
    String? name,
    Value<String?> code = const Value.absent(),
    int? centerId,
    String? centerName,
  }) => CachedHalaqa(
    id: id ?? this.id,
    name: name ?? this.name,
    code: code.present ? code.value : this.code,
    centerId: centerId ?? this.centerId,
    centerName: centerName ?? this.centerName,
  );
  CachedHalaqa copyWithCompanion(CachedHalaqasCompanion data) {
    return CachedHalaqa(
      id: data.id.present ? data.id.value : this.id,
      name: data.name.present ? data.name.value : this.name,
      code: data.code.present ? data.code.value : this.code,
      centerId: data.centerId.present ? data.centerId.value : this.centerId,
      centerName: data.centerName.present
          ? data.centerName.value
          : this.centerName,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CachedHalaqa(')
          ..write('id: $id, ')
          ..write('name: $name, ')
          ..write('code: $code, ')
          ..write('centerId: $centerId, ')
          ..write('centerName: $centerName')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(id, name, code, centerId, centerName);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CachedHalaqa &&
          other.id == this.id &&
          other.name == this.name &&
          other.code == this.code &&
          other.centerId == this.centerId &&
          other.centerName == this.centerName);
}

class CachedHalaqasCompanion extends UpdateCompanion<CachedHalaqa> {
  final Value<int> id;
  final Value<String> name;
  final Value<String?> code;
  final Value<int> centerId;
  final Value<String> centerName;
  const CachedHalaqasCompanion({
    this.id = const Value.absent(),
    this.name = const Value.absent(),
    this.code = const Value.absent(),
    this.centerId = const Value.absent(),
    this.centerName = const Value.absent(),
  });
  CachedHalaqasCompanion.insert({
    this.id = const Value.absent(),
    required String name,
    this.code = const Value.absent(),
    required int centerId,
    required String centerName,
  }) : name = Value(name),
       centerId = Value(centerId),
       centerName = Value(centerName);
  static Insertable<CachedHalaqa> custom({
    Expression<int>? id,
    Expression<String>? name,
    Expression<String>? code,
    Expression<int>? centerId,
    Expression<String>? centerName,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (name != null) 'name': name,
      if (code != null) 'code': code,
      if (centerId != null) 'center_id': centerId,
      if (centerName != null) 'center_name': centerName,
    });
  }

  CachedHalaqasCompanion copyWith({
    Value<int>? id,
    Value<String>? name,
    Value<String?>? code,
    Value<int>? centerId,
    Value<String>? centerName,
  }) {
    return CachedHalaqasCompanion(
      id: id ?? this.id,
      name: name ?? this.name,
      code: code ?? this.code,
      centerId: centerId ?? this.centerId,
      centerName: centerName ?? this.centerName,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (code.present) {
      map['code'] = Variable<String>(code.value);
    }
    if (centerId.present) {
      map['center_id'] = Variable<int>(centerId.value);
    }
    if (centerName.present) {
      map['center_name'] = Variable<String>(centerName.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CachedHalaqasCompanion(')
          ..write('id: $id, ')
          ..write('name: $name, ')
          ..write('code: $code, ')
          ..write('centerId: $centerId, ')
          ..write('centerName: $centerName')
          ..write(')'))
        .toString();
  }
}

class $CachedStudentsTable extends CachedStudents
    with TableInfo<$CachedStudentsTable, CachedStudent> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CachedStudentsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _halaqaIdMeta = const VerificationMeta(
    'halaqaId',
  );
  @override
  late final GeneratedColumn<int> halaqaId = GeneratedColumn<int>(
    'halaqa_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _studentNumberMeta = const VerificationMeta(
    'studentNumber',
  );
  @override
  late final GeneratedColumn<String> studentNumber = GeneratedColumn<String>(
    'student_number',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _fullNameMeta = const VerificationMeta(
    'fullName',
  );
  @override
  late final GeneratedColumn<String> fullName = GeneratedColumn<String>(
    'full_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _recordedTodayMeta = const VerificationMeta(
    'recordedToday',
  );
  @override
  late final GeneratedColumn<bool> recordedToday = GeneratedColumn<bool>(
    'recorded_today',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("recorded_today" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  static const VerificationMeta _dailyRecordIdMeta = const VerificationMeta(
    'dailyRecordId',
  );
  @override
  late final GeneratedColumn<int> dailyRecordId = GeneratedColumn<int>(
    'daily_record_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    halaqaId,
    studentNumber,
    fullName,
    recordedToday,
    dailyRecordId,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cached_students';
  @override
  VerificationContext validateIntegrity(
    Insertable<CachedStudent> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('halaqa_id')) {
      context.handle(
        _halaqaIdMeta,
        halaqaId.isAcceptableOrUnknown(data['halaqa_id']!, _halaqaIdMeta),
      );
    } else if (isInserting) {
      context.missing(_halaqaIdMeta);
    }
    if (data.containsKey('student_number')) {
      context.handle(
        _studentNumberMeta,
        studentNumber.isAcceptableOrUnknown(
          data['student_number']!,
          _studentNumberMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_studentNumberMeta);
    }
    if (data.containsKey('full_name')) {
      context.handle(
        _fullNameMeta,
        fullName.isAcceptableOrUnknown(data['full_name']!, _fullNameMeta),
      );
    } else if (isInserting) {
      context.missing(_fullNameMeta);
    }
    if (data.containsKey('recorded_today')) {
      context.handle(
        _recordedTodayMeta,
        recordedToday.isAcceptableOrUnknown(
          data['recorded_today']!,
          _recordedTodayMeta,
        ),
      );
    }
    if (data.containsKey('daily_record_id')) {
      context.handle(
        _dailyRecordIdMeta,
        dailyRecordId.isAcceptableOrUnknown(
          data['daily_record_id']!,
          _dailyRecordIdMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  CachedStudent map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CachedStudent(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      halaqaId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}halaqa_id'],
      )!,
      studentNumber: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}student_number'],
      )!,
      fullName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}full_name'],
      )!,
      recordedToday: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}recorded_today'],
      )!,
      dailyRecordId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}daily_record_id'],
      ),
    );
  }

  @override
  $CachedStudentsTable createAlias(String alias) {
    return $CachedStudentsTable(attachedDatabase, alias);
  }
}

class CachedStudent extends DataClass implements Insertable<CachedStudent> {
  final int id;
  final int halaqaId;
  final String studentNumber;
  final String fullName;
  final bool recordedToday;
  final int? dailyRecordId;
  const CachedStudent({
    required this.id,
    required this.halaqaId,
    required this.studentNumber,
    required this.fullName,
    required this.recordedToday,
    this.dailyRecordId,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['halaqa_id'] = Variable<int>(halaqaId);
    map['student_number'] = Variable<String>(studentNumber);
    map['full_name'] = Variable<String>(fullName);
    map['recorded_today'] = Variable<bool>(recordedToday);
    if (!nullToAbsent || dailyRecordId != null) {
      map['daily_record_id'] = Variable<int>(dailyRecordId);
    }
    return map;
  }

  CachedStudentsCompanion toCompanion(bool nullToAbsent) {
    return CachedStudentsCompanion(
      id: Value(id),
      halaqaId: Value(halaqaId),
      studentNumber: Value(studentNumber),
      fullName: Value(fullName),
      recordedToday: Value(recordedToday),
      dailyRecordId: dailyRecordId == null && nullToAbsent
          ? const Value.absent()
          : Value(dailyRecordId),
    );
  }

  factory CachedStudent.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CachedStudent(
      id: serializer.fromJson<int>(json['id']),
      halaqaId: serializer.fromJson<int>(json['halaqaId']),
      studentNumber: serializer.fromJson<String>(json['studentNumber']),
      fullName: serializer.fromJson<String>(json['fullName']),
      recordedToday: serializer.fromJson<bool>(json['recordedToday']),
      dailyRecordId: serializer.fromJson<int?>(json['dailyRecordId']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'halaqaId': serializer.toJson<int>(halaqaId),
      'studentNumber': serializer.toJson<String>(studentNumber),
      'fullName': serializer.toJson<String>(fullName),
      'recordedToday': serializer.toJson<bool>(recordedToday),
      'dailyRecordId': serializer.toJson<int?>(dailyRecordId),
    };
  }

  CachedStudent copyWith({
    int? id,
    int? halaqaId,
    String? studentNumber,
    String? fullName,
    bool? recordedToday,
    Value<int?> dailyRecordId = const Value.absent(),
  }) => CachedStudent(
    id: id ?? this.id,
    halaqaId: halaqaId ?? this.halaqaId,
    studentNumber: studentNumber ?? this.studentNumber,
    fullName: fullName ?? this.fullName,
    recordedToday: recordedToday ?? this.recordedToday,
    dailyRecordId: dailyRecordId.present
        ? dailyRecordId.value
        : this.dailyRecordId,
  );
  CachedStudent copyWithCompanion(CachedStudentsCompanion data) {
    return CachedStudent(
      id: data.id.present ? data.id.value : this.id,
      halaqaId: data.halaqaId.present ? data.halaqaId.value : this.halaqaId,
      studentNumber: data.studentNumber.present
          ? data.studentNumber.value
          : this.studentNumber,
      fullName: data.fullName.present ? data.fullName.value : this.fullName,
      recordedToday: data.recordedToday.present
          ? data.recordedToday.value
          : this.recordedToday,
      dailyRecordId: data.dailyRecordId.present
          ? data.dailyRecordId.value
          : this.dailyRecordId,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CachedStudent(')
          ..write('id: $id, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('studentNumber: $studentNumber, ')
          ..write('fullName: $fullName, ')
          ..write('recordedToday: $recordedToday, ')
          ..write('dailyRecordId: $dailyRecordId')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    halaqaId,
    studentNumber,
    fullName,
    recordedToday,
    dailyRecordId,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CachedStudent &&
          other.id == this.id &&
          other.halaqaId == this.halaqaId &&
          other.studentNumber == this.studentNumber &&
          other.fullName == this.fullName &&
          other.recordedToday == this.recordedToday &&
          other.dailyRecordId == this.dailyRecordId);
}

class CachedStudentsCompanion extends UpdateCompanion<CachedStudent> {
  final Value<int> id;
  final Value<int> halaqaId;
  final Value<String> studentNumber;
  final Value<String> fullName;
  final Value<bool> recordedToday;
  final Value<int?> dailyRecordId;
  const CachedStudentsCompanion({
    this.id = const Value.absent(),
    this.halaqaId = const Value.absent(),
    this.studentNumber = const Value.absent(),
    this.fullName = const Value.absent(),
    this.recordedToday = const Value.absent(),
    this.dailyRecordId = const Value.absent(),
  });
  CachedStudentsCompanion.insert({
    this.id = const Value.absent(),
    required int halaqaId,
    required String studentNumber,
    required String fullName,
    this.recordedToday = const Value.absent(),
    this.dailyRecordId = const Value.absent(),
  }) : halaqaId = Value(halaqaId),
       studentNumber = Value(studentNumber),
       fullName = Value(fullName);
  static Insertable<CachedStudent> custom({
    Expression<int>? id,
    Expression<int>? halaqaId,
    Expression<String>? studentNumber,
    Expression<String>? fullName,
    Expression<bool>? recordedToday,
    Expression<int>? dailyRecordId,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (halaqaId != null) 'halaqa_id': halaqaId,
      if (studentNumber != null) 'student_number': studentNumber,
      if (fullName != null) 'full_name': fullName,
      if (recordedToday != null) 'recorded_today': recordedToday,
      if (dailyRecordId != null) 'daily_record_id': dailyRecordId,
    });
  }

  CachedStudentsCompanion copyWith({
    Value<int>? id,
    Value<int>? halaqaId,
    Value<String>? studentNumber,
    Value<String>? fullName,
    Value<bool>? recordedToday,
    Value<int?>? dailyRecordId,
  }) {
    return CachedStudentsCompanion(
      id: id ?? this.id,
      halaqaId: halaqaId ?? this.halaqaId,
      studentNumber: studentNumber ?? this.studentNumber,
      fullName: fullName ?? this.fullName,
      recordedToday: recordedToday ?? this.recordedToday,
      dailyRecordId: dailyRecordId ?? this.dailyRecordId,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (halaqaId.present) {
      map['halaqa_id'] = Variable<int>(halaqaId.value);
    }
    if (studentNumber.present) {
      map['student_number'] = Variable<String>(studentNumber.value);
    }
    if (fullName.present) {
      map['full_name'] = Variable<String>(fullName.value);
    }
    if (recordedToday.present) {
      map['recorded_today'] = Variable<bool>(recordedToday.value);
    }
    if (dailyRecordId.present) {
      map['daily_record_id'] = Variable<int>(dailyRecordId.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CachedStudentsCompanion(')
          ..write('id: $id, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('studentNumber: $studentNumber, ')
          ..write('fullName: $fullName, ')
          ..write('recordedToday: $recordedToday, ')
          ..write('dailyRecordId: $dailyRecordId')
          ..write(')'))
        .toString();
  }
}

class $CachedSurahsTable extends CachedSurahs
    with TableInfo<$CachedSurahsTable, CachedSurah> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CachedSurahsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nameArabicMeta = const VerificationMeta(
    'nameArabic',
  );
  @override
  late final GeneratedColumn<String> nameArabic = GeneratedColumn<String>(
    'name_arabic',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _versesCountMeta = const VerificationMeta(
    'versesCount',
  );
  @override
  late final GeneratedColumn<int> versesCount = GeneratedColumn<int>(
    'verses_count',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [id, nameArabic, versesCount];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cached_surahs';
  @override
  VerificationContext validateIntegrity(
    Insertable<CachedSurah> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('name_arabic')) {
      context.handle(
        _nameArabicMeta,
        nameArabic.isAcceptableOrUnknown(data['name_arabic']!, _nameArabicMeta),
      );
    } else if (isInserting) {
      context.missing(_nameArabicMeta);
    }
    if (data.containsKey('verses_count')) {
      context.handle(
        _versesCountMeta,
        versesCount.isAcceptableOrUnknown(
          data['verses_count']!,
          _versesCountMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_versesCountMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  CachedSurah map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CachedSurah(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      nameArabic: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name_arabic'],
      )!,
      versesCount: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}verses_count'],
      )!,
    );
  }

  @override
  $CachedSurahsTable createAlias(String alias) {
    return $CachedSurahsTable(attachedDatabase, alias);
  }
}

class CachedSurah extends DataClass implements Insertable<CachedSurah> {
  final int id;
  final String nameArabic;
  final int versesCount;
  const CachedSurah({
    required this.id,
    required this.nameArabic,
    required this.versesCount,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['name_arabic'] = Variable<String>(nameArabic);
    map['verses_count'] = Variable<int>(versesCount);
    return map;
  }

  CachedSurahsCompanion toCompanion(bool nullToAbsent) {
    return CachedSurahsCompanion(
      id: Value(id),
      nameArabic: Value(nameArabic),
      versesCount: Value(versesCount),
    );
  }

  factory CachedSurah.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CachedSurah(
      id: serializer.fromJson<int>(json['id']),
      nameArabic: serializer.fromJson<String>(json['nameArabic']),
      versesCount: serializer.fromJson<int>(json['versesCount']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'nameArabic': serializer.toJson<String>(nameArabic),
      'versesCount': serializer.toJson<int>(versesCount),
    };
  }

  CachedSurah copyWith({int? id, String? nameArabic, int? versesCount}) =>
      CachedSurah(
        id: id ?? this.id,
        nameArabic: nameArabic ?? this.nameArabic,
        versesCount: versesCount ?? this.versesCount,
      );
  CachedSurah copyWithCompanion(CachedSurahsCompanion data) {
    return CachedSurah(
      id: data.id.present ? data.id.value : this.id,
      nameArabic: data.nameArabic.present
          ? data.nameArabic.value
          : this.nameArabic,
      versesCount: data.versesCount.present
          ? data.versesCount.value
          : this.versesCount,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CachedSurah(')
          ..write('id: $id, ')
          ..write('nameArabic: $nameArabic, ')
          ..write('versesCount: $versesCount')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(id, nameArabic, versesCount);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CachedSurah &&
          other.id == this.id &&
          other.nameArabic == this.nameArabic &&
          other.versesCount == this.versesCount);
}

class CachedSurahsCompanion extends UpdateCompanion<CachedSurah> {
  final Value<int> id;
  final Value<String> nameArabic;
  final Value<int> versesCount;
  const CachedSurahsCompanion({
    this.id = const Value.absent(),
    this.nameArabic = const Value.absent(),
    this.versesCount = const Value.absent(),
  });
  CachedSurahsCompanion.insert({
    this.id = const Value.absent(),
    required String nameArabic,
    required int versesCount,
  }) : nameArabic = Value(nameArabic),
       versesCount = Value(versesCount);
  static Insertable<CachedSurah> custom({
    Expression<int>? id,
    Expression<String>? nameArabic,
    Expression<int>? versesCount,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (nameArabic != null) 'name_arabic': nameArabic,
      if (versesCount != null) 'verses_count': versesCount,
    });
  }

  CachedSurahsCompanion copyWith({
    Value<int>? id,
    Value<String>? nameArabic,
    Value<int>? versesCount,
  }) {
    return CachedSurahsCompanion(
      id: id ?? this.id,
      nameArabic: nameArabic ?? this.nameArabic,
      versesCount: versesCount ?? this.versesCount,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (nameArabic.present) {
      map['name_arabic'] = Variable<String>(nameArabic.value);
    }
    if (versesCount.present) {
      map['verses_count'] = Variable<int>(versesCount.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CachedSurahsCompanion(')
          ..write('id: $id, ')
          ..write('nameArabic: $nameArabic, ')
          ..write('versesCount: $versesCount')
          ..write(')'))
        .toString();
  }
}

class $CachedAyahsTable extends CachedAyahs
    with TableInfo<$CachedAyahsTable, CachedAyah> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CachedAyahsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _surahIdMeta = const VerificationMeta(
    'surahId',
  );
  @override
  late final GeneratedColumn<int> surahId = GeneratedColumn<int>(
    'surah_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _ayahNumberMeta = const VerificationMeta(
    'ayahNumber',
  );
  @override
  late final GeneratedColumn<int> ayahNumber = GeneratedColumn<int>(
    'ayah_number',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _globalOrderMeta = const VerificationMeta(
    'globalOrder',
  );
  @override
  late final GeneratedColumn<int> globalOrder = GeneratedColumn<int>(
    'global_order',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _juzMeta = const VerificationMeta('juz');
  @override
  late final GeneratedColumn<int> juz = GeneratedColumn<int>(
    'juz',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    surahId,
    ayahNumber,
    globalOrder,
    juz,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cached_ayahs';
  @override
  VerificationContext validateIntegrity(
    Insertable<CachedAyah> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('surah_id')) {
      context.handle(
        _surahIdMeta,
        surahId.isAcceptableOrUnknown(data['surah_id']!, _surahIdMeta),
      );
    } else if (isInserting) {
      context.missing(_surahIdMeta);
    }
    if (data.containsKey('ayah_number')) {
      context.handle(
        _ayahNumberMeta,
        ayahNumber.isAcceptableOrUnknown(data['ayah_number']!, _ayahNumberMeta),
      );
    } else if (isInserting) {
      context.missing(_ayahNumberMeta);
    }
    if (data.containsKey('global_order')) {
      context.handle(
        _globalOrderMeta,
        globalOrder.isAcceptableOrUnknown(
          data['global_order']!,
          _globalOrderMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_globalOrderMeta);
    }
    if (data.containsKey('juz')) {
      context.handle(
        _juzMeta,
        juz.isAcceptableOrUnknown(data['juz']!, _juzMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  CachedAyah map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CachedAyah(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      surahId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}surah_id'],
      )!,
      ayahNumber: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}ayah_number'],
      )!,
      globalOrder: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}global_order'],
      )!,
      juz: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}juz'],
      ),
    );
  }

  @override
  $CachedAyahsTable createAlias(String alias) {
    return $CachedAyahsTable(attachedDatabase, alias);
  }
}

class CachedAyah extends DataClass implements Insertable<CachedAyah> {
  final int id;
  final int surahId;
  final int ayahNumber;
  final int globalOrder;
  final int? juz;
  const CachedAyah({
    required this.id,
    required this.surahId,
    required this.ayahNumber,
    required this.globalOrder,
    this.juz,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['surah_id'] = Variable<int>(surahId);
    map['ayah_number'] = Variable<int>(ayahNumber);
    map['global_order'] = Variable<int>(globalOrder);
    if (!nullToAbsent || juz != null) {
      map['juz'] = Variable<int>(juz);
    }
    return map;
  }

  CachedAyahsCompanion toCompanion(bool nullToAbsent) {
    return CachedAyahsCompanion(
      id: Value(id),
      surahId: Value(surahId),
      ayahNumber: Value(ayahNumber),
      globalOrder: Value(globalOrder),
      juz: juz == null && nullToAbsent ? const Value.absent() : Value(juz),
    );
  }

  factory CachedAyah.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CachedAyah(
      id: serializer.fromJson<int>(json['id']),
      surahId: serializer.fromJson<int>(json['surahId']),
      ayahNumber: serializer.fromJson<int>(json['ayahNumber']),
      globalOrder: serializer.fromJson<int>(json['globalOrder']),
      juz: serializer.fromJson<int?>(json['juz']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'surahId': serializer.toJson<int>(surahId),
      'ayahNumber': serializer.toJson<int>(ayahNumber),
      'globalOrder': serializer.toJson<int>(globalOrder),
      'juz': serializer.toJson<int?>(juz),
    };
  }

  CachedAyah copyWith({
    int? id,
    int? surahId,
    int? ayahNumber,
    int? globalOrder,
    Value<int?> juz = const Value.absent(),
  }) => CachedAyah(
    id: id ?? this.id,
    surahId: surahId ?? this.surahId,
    ayahNumber: ayahNumber ?? this.ayahNumber,
    globalOrder: globalOrder ?? this.globalOrder,
    juz: juz.present ? juz.value : this.juz,
  );
  CachedAyah copyWithCompanion(CachedAyahsCompanion data) {
    return CachedAyah(
      id: data.id.present ? data.id.value : this.id,
      surahId: data.surahId.present ? data.surahId.value : this.surahId,
      ayahNumber: data.ayahNumber.present
          ? data.ayahNumber.value
          : this.ayahNumber,
      globalOrder: data.globalOrder.present
          ? data.globalOrder.value
          : this.globalOrder,
      juz: data.juz.present ? data.juz.value : this.juz,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CachedAyah(')
          ..write('id: $id, ')
          ..write('surahId: $surahId, ')
          ..write('ayahNumber: $ayahNumber, ')
          ..write('globalOrder: $globalOrder, ')
          ..write('juz: $juz')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(id, surahId, ayahNumber, globalOrder, juz);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CachedAyah &&
          other.id == this.id &&
          other.surahId == this.surahId &&
          other.ayahNumber == this.ayahNumber &&
          other.globalOrder == this.globalOrder &&
          other.juz == this.juz);
}

class CachedAyahsCompanion extends UpdateCompanion<CachedAyah> {
  final Value<int> id;
  final Value<int> surahId;
  final Value<int> ayahNumber;
  final Value<int> globalOrder;
  final Value<int?> juz;
  const CachedAyahsCompanion({
    this.id = const Value.absent(),
    this.surahId = const Value.absent(),
    this.ayahNumber = const Value.absent(),
    this.globalOrder = const Value.absent(),
    this.juz = const Value.absent(),
  });
  CachedAyahsCompanion.insert({
    this.id = const Value.absent(),
    required int surahId,
    required int ayahNumber,
    required int globalOrder,
    this.juz = const Value.absent(),
  }) : surahId = Value(surahId),
       ayahNumber = Value(ayahNumber),
       globalOrder = Value(globalOrder);
  static Insertable<CachedAyah> custom({
    Expression<int>? id,
    Expression<int>? surahId,
    Expression<int>? ayahNumber,
    Expression<int>? globalOrder,
    Expression<int>? juz,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (surahId != null) 'surah_id': surahId,
      if (ayahNumber != null) 'ayah_number': ayahNumber,
      if (globalOrder != null) 'global_order': globalOrder,
      if (juz != null) 'juz': juz,
    });
  }

  CachedAyahsCompanion copyWith({
    Value<int>? id,
    Value<int>? surahId,
    Value<int>? ayahNumber,
    Value<int>? globalOrder,
    Value<int?>? juz,
  }) {
    return CachedAyahsCompanion(
      id: id ?? this.id,
      surahId: surahId ?? this.surahId,
      ayahNumber: ayahNumber ?? this.ayahNumber,
      globalOrder: globalOrder ?? this.globalOrder,
      juz: juz ?? this.juz,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (surahId.present) {
      map['surah_id'] = Variable<int>(surahId.value);
    }
    if (ayahNumber.present) {
      map['ayah_number'] = Variable<int>(ayahNumber.value);
    }
    if (globalOrder.present) {
      map['global_order'] = Variable<int>(globalOrder.value);
    }
    if (juz.present) {
      map['juz'] = Variable<int>(juz.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CachedAyahsCompanion(')
          ..write('id: $id, ')
          ..write('surahId: $surahId, ')
          ..write('ayahNumber: $ayahNumber, ')
          ..write('globalOrder: $globalOrder, ')
          ..write('juz: $juz')
          ..write(')'))
        .toString();
  }
}

class $PendingDailyRecordsTable extends PendingDailyRecords
    with TableInfo<$PendingDailyRecordsTable, PendingDailyRecord> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $PendingDailyRecordsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _operationUuidMeta = const VerificationMeta(
    'operationUuid',
  );
  @override
  late final GeneratedColumn<String> operationUuid = GeneratedColumn<String>(
    'operation_uuid',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _studentIdMeta = const VerificationMeta(
    'studentId',
  );
  @override
  late final GeneratedColumn<int> studentId = GeneratedColumn<int>(
    'student_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _studentClientUuidMeta = const VerificationMeta(
    'studentClientUuid',
  );
  @override
  late final GeneratedColumn<String> studentClientUuid =
      GeneratedColumn<String>(
        'student_client_uuid',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _halaqaIdMeta = const VerificationMeta(
    'halaqaId',
  );
  @override
  late final GeneratedColumn<int> halaqaId = GeneratedColumn<int>(
    'halaqa_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _recordDateMeta = const VerificationMeta(
    'recordDate',
  );
  @override
  late final GeneratedColumn<DateTime> recordDate = GeneratedColumn<DateTime>(
    'record_date',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _payloadJsonMeta = const VerificationMeta(
    'payloadJson',
  );
  @override
  late final GeneratedColumn<String> payloadJson = GeneratedColumn<String>(
    'payload_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('pending'),
  );
  static const VerificationMeta _attemptsMeta = const VerificationMeta(
    'attempts',
  );
  @override
  late final GeneratedColumn<int> attempts = GeneratedColumn<int>(
    'attempts',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _lastErrorMeta = const VerificationMeta(
    'lastError',
  );
  @override
  late final GeneratedColumn<String> lastError = GeneratedColumn<String>(
    'last_error',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _serverRecordIdMeta = const VerificationMeta(
    'serverRecordId',
  );
  @override
  late final GeneratedColumn<int> serverRecordId = GeneratedColumn<int>(
    'server_record_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _clientCreatedAtMeta = const VerificationMeta(
    'clientCreatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> clientCreatedAt =
      GeneratedColumn<DateTime>(
        'client_created_at',
        aliasedName,
        false,
        type: DriftSqlType.dateTime,
        requiredDuringInsert: true,
      );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    operationUuid,
    studentId,
    studentClientUuid,
    halaqaId,
    recordDate,
    payloadJson,
    status,
    attempts,
    lastError,
    serverRecordId,
    clientCreatedAt,
    updatedAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'pending_daily_records';
  @override
  VerificationContext validateIntegrity(
    Insertable<PendingDailyRecord> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('operation_uuid')) {
      context.handle(
        _operationUuidMeta,
        operationUuid.isAcceptableOrUnknown(
          data['operation_uuid']!,
          _operationUuidMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operationUuidMeta);
    }
    if (data.containsKey('student_id')) {
      context.handle(
        _studentIdMeta,
        studentId.isAcceptableOrUnknown(data['student_id']!, _studentIdMeta),
      );
    } else if (isInserting) {
      context.missing(_studentIdMeta);
    }
    if (data.containsKey('student_client_uuid')) {
      context.handle(
        _studentClientUuidMeta,
        studentClientUuid.isAcceptableOrUnknown(
          data['student_client_uuid']!,
          _studentClientUuidMeta,
        ),
      );
    }
    if (data.containsKey('halaqa_id')) {
      context.handle(
        _halaqaIdMeta,
        halaqaId.isAcceptableOrUnknown(data['halaqa_id']!, _halaqaIdMeta),
      );
    } else if (isInserting) {
      context.missing(_halaqaIdMeta);
    }
    if (data.containsKey('record_date')) {
      context.handle(
        _recordDateMeta,
        recordDate.isAcceptableOrUnknown(data['record_date']!, _recordDateMeta),
      );
    } else if (isInserting) {
      context.missing(_recordDateMeta);
    }
    if (data.containsKey('payload_json')) {
      context.handle(
        _payloadJsonMeta,
        payloadJson.isAcceptableOrUnknown(
          data['payload_json']!,
          _payloadJsonMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadJsonMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('attempts')) {
      context.handle(
        _attemptsMeta,
        attempts.isAcceptableOrUnknown(data['attempts']!, _attemptsMeta),
      );
    }
    if (data.containsKey('last_error')) {
      context.handle(
        _lastErrorMeta,
        lastError.isAcceptableOrUnknown(data['last_error']!, _lastErrorMeta),
      );
    }
    if (data.containsKey('server_record_id')) {
      context.handle(
        _serverRecordIdMeta,
        serverRecordId.isAcceptableOrUnknown(
          data['server_record_id']!,
          _serverRecordIdMeta,
        ),
      );
    }
    if (data.containsKey('client_created_at')) {
      context.handle(
        _clientCreatedAtMeta,
        clientCreatedAt.isAcceptableOrUnknown(
          data['client_created_at']!,
          _clientCreatedAtMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_clientCreatedAtMeta);
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {operationUuid};
  @override
  PendingDailyRecord map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return PendingDailyRecord(
      operationUuid: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operation_uuid'],
      )!,
      studentId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}student_id'],
      )!,
      studentClientUuid: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}student_client_uuid'],
      ),
      halaqaId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}halaqa_id'],
      )!,
      recordDate: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}record_date'],
      )!,
      payloadJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_json'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      )!,
      attempts: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}attempts'],
      )!,
      lastError: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}last_error'],
      ),
      serverRecordId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}server_record_id'],
      ),
      clientCreatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}client_created_at'],
      )!,
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $PendingDailyRecordsTable createAlias(String alias) {
    return $PendingDailyRecordsTable(attachedDatabase, alias);
  }
}

class PendingDailyRecord extends DataClass
    implements Insertable<PendingDailyRecord> {
  final String operationUuid;
  final int studentId;
  final String? studentClientUuid;
  final int halaqaId;
  final DateTime recordDate;
  final String payloadJson;
  final String status;
  final int attempts;
  final String? lastError;
  final int? serverRecordId;
  final DateTime clientCreatedAt;
  final DateTime updatedAt;
  const PendingDailyRecord({
    required this.operationUuid,
    required this.studentId,
    this.studentClientUuid,
    required this.halaqaId,
    required this.recordDate,
    required this.payloadJson,
    required this.status,
    required this.attempts,
    this.lastError,
    this.serverRecordId,
    required this.clientCreatedAt,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['operation_uuid'] = Variable<String>(operationUuid);
    map['student_id'] = Variable<int>(studentId);
    if (!nullToAbsent || studentClientUuid != null) {
      map['student_client_uuid'] = Variable<String>(studentClientUuid);
    }
    map['halaqa_id'] = Variable<int>(halaqaId);
    map['record_date'] = Variable<DateTime>(recordDate);
    map['payload_json'] = Variable<String>(payloadJson);
    map['status'] = Variable<String>(status);
    map['attempts'] = Variable<int>(attempts);
    if (!nullToAbsent || lastError != null) {
      map['last_error'] = Variable<String>(lastError);
    }
    if (!nullToAbsent || serverRecordId != null) {
      map['server_record_id'] = Variable<int>(serverRecordId);
    }
    map['client_created_at'] = Variable<DateTime>(clientCreatedAt);
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  PendingDailyRecordsCompanion toCompanion(bool nullToAbsent) {
    return PendingDailyRecordsCompanion(
      operationUuid: Value(operationUuid),
      studentId: Value(studentId),
      studentClientUuid: studentClientUuid == null && nullToAbsent
          ? const Value.absent()
          : Value(studentClientUuid),
      halaqaId: Value(halaqaId),
      recordDate: Value(recordDate),
      payloadJson: Value(payloadJson),
      status: Value(status),
      attempts: Value(attempts),
      lastError: lastError == null && nullToAbsent
          ? const Value.absent()
          : Value(lastError),
      serverRecordId: serverRecordId == null && nullToAbsent
          ? const Value.absent()
          : Value(serverRecordId),
      clientCreatedAt: Value(clientCreatedAt),
      updatedAt: Value(updatedAt),
    );
  }

  factory PendingDailyRecord.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return PendingDailyRecord(
      operationUuid: serializer.fromJson<String>(json['operationUuid']),
      studentId: serializer.fromJson<int>(json['studentId']),
      studentClientUuid: serializer.fromJson<String?>(
        json['studentClientUuid'],
      ),
      halaqaId: serializer.fromJson<int>(json['halaqaId']),
      recordDate: serializer.fromJson<DateTime>(json['recordDate']),
      payloadJson: serializer.fromJson<String>(json['payloadJson']),
      status: serializer.fromJson<String>(json['status']),
      attempts: serializer.fromJson<int>(json['attempts']),
      lastError: serializer.fromJson<String?>(json['lastError']),
      serverRecordId: serializer.fromJson<int?>(json['serverRecordId']),
      clientCreatedAt: serializer.fromJson<DateTime>(json['clientCreatedAt']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'operationUuid': serializer.toJson<String>(operationUuid),
      'studentId': serializer.toJson<int>(studentId),
      'studentClientUuid': serializer.toJson<String?>(studentClientUuid),
      'halaqaId': serializer.toJson<int>(halaqaId),
      'recordDate': serializer.toJson<DateTime>(recordDate),
      'payloadJson': serializer.toJson<String>(payloadJson),
      'status': serializer.toJson<String>(status),
      'attempts': serializer.toJson<int>(attempts),
      'lastError': serializer.toJson<String?>(lastError),
      'serverRecordId': serializer.toJson<int?>(serverRecordId),
      'clientCreatedAt': serializer.toJson<DateTime>(clientCreatedAt),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  PendingDailyRecord copyWith({
    String? operationUuid,
    int? studentId,
    Value<String?> studentClientUuid = const Value.absent(),
    int? halaqaId,
    DateTime? recordDate,
    String? payloadJson,
    String? status,
    int? attempts,
    Value<String?> lastError = const Value.absent(),
    Value<int?> serverRecordId = const Value.absent(),
    DateTime? clientCreatedAt,
    DateTime? updatedAt,
  }) => PendingDailyRecord(
    operationUuid: operationUuid ?? this.operationUuid,
    studentId: studentId ?? this.studentId,
    studentClientUuid: studentClientUuid.present
        ? studentClientUuid.value
        : this.studentClientUuid,
    halaqaId: halaqaId ?? this.halaqaId,
    recordDate: recordDate ?? this.recordDate,
    payloadJson: payloadJson ?? this.payloadJson,
    status: status ?? this.status,
    attempts: attempts ?? this.attempts,
    lastError: lastError.present ? lastError.value : this.lastError,
    serverRecordId: serverRecordId.present
        ? serverRecordId.value
        : this.serverRecordId,
    clientCreatedAt: clientCreatedAt ?? this.clientCreatedAt,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  PendingDailyRecord copyWithCompanion(PendingDailyRecordsCompanion data) {
    return PendingDailyRecord(
      operationUuid: data.operationUuid.present
          ? data.operationUuid.value
          : this.operationUuid,
      studentId: data.studentId.present ? data.studentId.value : this.studentId,
      studentClientUuid: data.studentClientUuid.present
          ? data.studentClientUuid.value
          : this.studentClientUuid,
      halaqaId: data.halaqaId.present ? data.halaqaId.value : this.halaqaId,
      recordDate: data.recordDate.present
          ? data.recordDate.value
          : this.recordDate,
      payloadJson: data.payloadJson.present
          ? data.payloadJson.value
          : this.payloadJson,
      status: data.status.present ? data.status.value : this.status,
      attempts: data.attempts.present ? data.attempts.value : this.attempts,
      lastError: data.lastError.present ? data.lastError.value : this.lastError,
      serverRecordId: data.serverRecordId.present
          ? data.serverRecordId.value
          : this.serverRecordId,
      clientCreatedAt: data.clientCreatedAt.present
          ? data.clientCreatedAt.value
          : this.clientCreatedAt,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('PendingDailyRecord(')
          ..write('operationUuid: $operationUuid, ')
          ..write('studentId: $studentId, ')
          ..write('studentClientUuid: $studentClientUuid, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('recordDate: $recordDate, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('status: $status, ')
          ..write('attempts: $attempts, ')
          ..write('lastError: $lastError, ')
          ..write('serverRecordId: $serverRecordId, ')
          ..write('clientCreatedAt: $clientCreatedAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    operationUuid,
    studentId,
    studentClientUuid,
    halaqaId,
    recordDate,
    payloadJson,
    status,
    attempts,
    lastError,
    serverRecordId,
    clientCreatedAt,
    updatedAt,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is PendingDailyRecord &&
          other.operationUuid == this.operationUuid &&
          other.studentId == this.studentId &&
          other.studentClientUuid == this.studentClientUuid &&
          other.halaqaId == this.halaqaId &&
          other.recordDate == this.recordDate &&
          other.payloadJson == this.payloadJson &&
          other.status == this.status &&
          other.attempts == this.attempts &&
          other.lastError == this.lastError &&
          other.serverRecordId == this.serverRecordId &&
          other.clientCreatedAt == this.clientCreatedAt &&
          other.updatedAt == this.updatedAt);
}

class PendingDailyRecordsCompanion extends UpdateCompanion<PendingDailyRecord> {
  final Value<String> operationUuid;
  final Value<int> studentId;
  final Value<String?> studentClientUuid;
  final Value<int> halaqaId;
  final Value<DateTime> recordDate;
  final Value<String> payloadJson;
  final Value<String> status;
  final Value<int> attempts;
  final Value<String?> lastError;
  final Value<int?> serverRecordId;
  final Value<DateTime> clientCreatedAt;
  final Value<DateTime> updatedAt;
  final Value<int> rowid;
  const PendingDailyRecordsCompanion({
    this.operationUuid = const Value.absent(),
    this.studentId = const Value.absent(),
    this.studentClientUuid = const Value.absent(),
    this.halaqaId = const Value.absent(),
    this.recordDate = const Value.absent(),
    this.payloadJson = const Value.absent(),
    this.status = const Value.absent(),
    this.attempts = const Value.absent(),
    this.lastError = const Value.absent(),
    this.serverRecordId = const Value.absent(),
    this.clientCreatedAt = const Value.absent(),
    this.updatedAt = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  PendingDailyRecordsCompanion.insert({
    required String operationUuid,
    required int studentId,
    this.studentClientUuid = const Value.absent(),
    required int halaqaId,
    required DateTime recordDate,
    required String payloadJson,
    this.status = const Value.absent(),
    this.attempts = const Value.absent(),
    this.lastError = const Value.absent(),
    this.serverRecordId = const Value.absent(),
    required DateTime clientCreatedAt,
    required DateTime updatedAt,
    this.rowid = const Value.absent(),
  }) : operationUuid = Value(operationUuid),
       studentId = Value(studentId),
       halaqaId = Value(halaqaId),
       recordDate = Value(recordDate),
       payloadJson = Value(payloadJson),
       clientCreatedAt = Value(clientCreatedAt),
       updatedAt = Value(updatedAt);
  static Insertable<PendingDailyRecord> custom({
    Expression<String>? operationUuid,
    Expression<int>? studentId,
    Expression<String>? studentClientUuid,
    Expression<int>? halaqaId,
    Expression<DateTime>? recordDate,
    Expression<String>? payloadJson,
    Expression<String>? status,
    Expression<int>? attempts,
    Expression<String>? lastError,
    Expression<int>? serverRecordId,
    Expression<DateTime>? clientCreatedAt,
    Expression<DateTime>? updatedAt,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (operationUuid != null) 'operation_uuid': operationUuid,
      if (studentId != null) 'student_id': studentId,
      if (studentClientUuid != null) 'student_client_uuid': studentClientUuid,
      if (halaqaId != null) 'halaqa_id': halaqaId,
      if (recordDate != null) 'record_date': recordDate,
      if (payloadJson != null) 'payload_json': payloadJson,
      if (status != null) 'status': status,
      if (attempts != null) 'attempts': attempts,
      if (lastError != null) 'last_error': lastError,
      if (serverRecordId != null) 'server_record_id': serverRecordId,
      if (clientCreatedAt != null) 'client_created_at': clientCreatedAt,
      if (updatedAt != null) 'updated_at': updatedAt,
      if (rowid != null) 'rowid': rowid,
    });
  }

  PendingDailyRecordsCompanion copyWith({
    Value<String>? operationUuid,
    Value<int>? studentId,
    Value<String?>? studentClientUuid,
    Value<int>? halaqaId,
    Value<DateTime>? recordDate,
    Value<String>? payloadJson,
    Value<String>? status,
    Value<int>? attempts,
    Value<String?>? lastError,
    Value<int?>? serverRecordId,
    Value<DateTime>? clientCreatedAt,
    Value<DateTime>? updatedAt,
    Value<int>? rowid,
  }) {
    return PendingDailyRecordsCompanion(
      operationUuid: operationUuid ?? this.operationUuid,
      studentId: studentId ?? this.studentId,
      studentClientUuid: studentClientUuid ?? this.studentClientUuid,
      halaqaId: halaqaId ?? this.halaqaId,
      recordDate: recordDate ?? this.recordDate,
      payloadJson: payloadJson ?? this.payloadJson,
      status: status ?? this.status,
      attempts: attempts ?? this.attempts,
      lastError: lastError ?? this.lastError,
      serverRecordId: serverRecordId ?? this.serverRecordId,
      clientCreatedAt: clientCreatedAt ?? this.clientCreatedAt,
      updatedAt: updatedAt ?? this.updatedAt,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (operationUuid.present) {
      map['operation_uuid'] = Variable<String>(operationUuid.value);
    }
    if (studentId.present) {
      map['student_id'] = Variable<int>(studentId.value);
    }
    if (studentClientUuid.present) {
      map['student_client_uuid'] = Variable<String>(studentClientUuid.value);
    }
    if (halaqaId.present) {
      map['halaqa_id'] = Variable<int>(halaqaId.value);
    }
    if (recordDate.present) {
      map['record_date'] = Variable<DateTime>(recordDate.value);
    }
    if (payloadJson.present) {
      map['payload_json'] = Variable<String>(payloadJson.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (attempts.present) {
      map['attempts'] = Variable<int>(attempts.value);
    }
    if (lastError.present) {
      map['last_error'] = Variable<String>(lastError.value);
    }
    if (serverRecordId.present) {
      map['server_record_id'] = Variable<int>(serverRecordId.value);
    }
    if (clientCreatedAt.present) {
      map['client_created_at'] = Variable<DateTime>(clientCreatedAt.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('PendingDailyRecordsCompanion(')
          ..write('operationUuid: $operationUuid, ')
          ..write('studentId: $studentId, ')
          ..write('studentClientUuid: $studentClientUuid, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('recordDate: $recordDate, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('status: $status, ')
          ..write('attempts: $attempts, ')
          ..write('lastError: $lastError, ')
          ..write('serverRecordId: $serverRecordId, ')
          ..write('clientCreatedAt: $clientCreatedAt, ')
          ..write('updatedAt: $updatedAt, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $CachedTeacherProfilesTable extends CachedTeacherProfiles
    with TableInfo<$CachedTeacherProfilesTable, CachedTeacherProfile> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CachedTeacherProfilesTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _payloadJsonMeta = const VerificationMeta(
    'payloadJson',
  );
  @override
  late final GeneratedColumn<String> payloadJson = GeneratedColumn<String>(
    'payload_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [id, payloadJson, updatedAt];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cached_teacher_profiles';
  @override
  VerificationContext validateIntegrity(
    Insertable<CachedTeacherProfile> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('payload_json')) {
      context.handle(
        _payloadJsonMeta,
        payloadJson.isAcceptableOrUnknown(
          data['payload_json']!,
          _payloadJsonMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadJsonMeta);
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  CachedTeacherProfile map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CachedTeacherProfile(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      payloadJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_json'],
      )!,
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $CachedTeacherProfilesTable createAlias(String alias) {
    return $CachedTeacherProfilesTable(attachedDatabase, alias);
  }
}

class CachedTeacherProfile extends DataClass
    implements Insertable<CachedTeacherProfile> {
  final int id;
  final String payloadJson;
  final DateTime updatedAt;
  const CachedTeacherProfile({
    required this.id,
    required this.payloadJson,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['payload_json'] = Variable<String>(payloadJson);
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  CachedTeacherProfilesCompanion toCompanion(bool nullToAbsent) {
    return CachedTeacherProfilesCompanion(
      id: Value(id),
      payloadJson: Value(payloadJson),
      updatedAt: Value(updatedAt),
    );
  }

  factory CachedTeacherProfile.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CachedTeacherProfile(
      id: serializer.fromJson<int>(json['id']),
      payloadJson: serializer.fromJson<String>(json['payloadJson']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'payloadJson': serializer.toJson<String>(payloadJson),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  CachedTeacherProfile copyWith({
    int? id,
    String? payloadJson,
    DateTime? updatedAt,
  }) => CachedTeacherProfile(
    id: id ?? this.id,
    payloadJson: payloadJson ?? this.payloadJson,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  CachedTeacherProfile copyWithCompanion(CachedTeacherProfilesCompanion data) {
    return CachedTeacherProfile(
      id: data.id.present ? data.id.value : this.id,
      payloadJson: data.payloadJson.present
          ? data.payloadJson.value
          : this.payloadJson,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CachedTeacherProfile(')
          ..write('id: $id, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(id, payloadJson, updatedAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CachedTeacherProfile &&
          other.id == this.id &&
          other.payloadJson == this.payloadJson &&
          other.updatedAt == this.updatedAt);
}

class CachedTeacherProfilesCompanion
    extends UpdateCompanion<CachedTeacherProfile> {
  final Value<int> id;
  final Value<String> payloadJson;
  final Value<DateTime> updatedAt;
  const CachedTeacherProfilesCompanion({
    this.id = const Value.absent(),
    this.payloadJson = const Value.absent(),
    this.updatedAt = const Value.absent(),
  });
  CachedTeacherProfilesCompanion.insert({
    this.id = const Value.absent(),
    required String payloadJson,
    required DateTime updatedAt,
  }) : payloadJson = Value(payloadJson),
       updatedAt = Value(updatedAt);
  static Insertable<CachedTeacherProfile> custom({
    Expression<int>? id,
    Expression<String>? payloadJson,
    Expression<DateTime>? updatedAt,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (payloadJson != null) 'payload_json': payloadJson,
      if (updatedAt != null) 'updated_at': updatedAt,
    });
  }

  CachedTeacherProfilesCompanion copyWith({
    Value<int>? id,
    Value<String>? payloadJson,
    Value<DateTime>? updatedAt,
  }) {
    return CachedTeacherProfilesCompanion(
      id: id ?? this.id,
      payloadJson: payloadJson ?? this.payloadJson,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (payloadJson.present) {
      map['payload_json'] = Variable<String>(payloadJson.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CachedTeacherProfilesCompanion(')
          ..write('id: $id, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }
}

class $CachedStudentProfilesTable extends CachedStudentProfiles
    with TableInfo<$CachedStudentProfilesTable, CachedStudentProfile> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CachedStudentProfilesTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _studentIdMeta = const VerificationMeta(
    'studentId',
  );
  @override
  late final GeneratedColumn<int> studentId = GeneratedColumn<int>(
    'student_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _payloadJsonMeta = const VerificationMeta(
    'payloadJson',
  );
  @override
  late final GeneratedColumn<String> payloadJson = GeneratedColumn<String>(
    'payload_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [studentId, payloadJson, updatedAt];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cached_student_profiles';
  @override
  VerificationContext validateIntegrity(
    Insertable<CachedStudentProfile> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('student_id')) {
      context.handle(
        _studentIdMeta,
        studentId.isAcceptableOrUnknown(data['student_id']!, _studentIdMeta),
      );
    }
    if (data.containsKey('payload_json')) {
      context.handle(
        _payloadJsonMeta,
        payloadJson.isAcceptableOrUnknown(
          data['payload_json']!,
          _payloadJsonMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadJsonMeta);
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {studentId};
  @override
  CachedStudentProfile map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CachedStudentProfile(
      studentId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}student_id'],
      )!,
      payloadJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_json'],
      )!,
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $CachedStudentProfilesTable createAlias(String alias) {
    return $CachedStudentProfilesTable(attachedDatabase, alias);
  }
}

class CachedStudentProfile extends DataClass
    implements Insertable<CachedStudentProfile> {
  final int studentId;
  final String payloadJson;
  final DateTime updatedAt;
  const CachedStudentProfile({
    required this.studentId,
    required this.payloadJson,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['student_id'] = Variable<int>(studentId);
    map['payload_json'] = Variable<String>(payloadJson);
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  CachedStudentProfilesCompanion toCompanion(bool nullToAbsent) {
    return CachedStudentProfilesCompanion(
      studentId: Value(studentId),
      payloadJson: Value(payloadJson),
      updatedAt: Value(updatedAt),
    );
  }

  factory CachedStudentProfile.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CachedStudentProfile(
      studentId: serializer.fromJson<int>(json['studentId']),
      payloadJson: serializer.fromJson<String>(json['payloadJson']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'studentId': serializer.toJson<int>(studentId),
      'payloadJson': serializer.toJson<String>(payloadJson),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  CachedStudentProfile copyWith({
    int? studentId,
    String? payloadJson,
    DateTime? updatedAt,
  }) => CachedStudentProfile(
    studentId: studentId ?? this.studentId,
    payloadJson: payloadJson ?? this.payloadJson,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  CachedStudentProfile copyWithCompanion(CachedStudentProfilesCompanion data) {
    return CachedStudentProfile(
      studentId: data.studentId.present ? data.studentId.value : this.studentId,
      payloadJson: data.payloadJson.present
          ? data.payloadJson.value
          : this.payloadJson,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CachedStudentProfile(')
          ..write('studentId: $studentId, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(studentId, payloadJson, updatedAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CachedStudentProfile &&
          other.studentId == this.studentId &&
          other.payloadJson == this.payloadJson &&
          other.updatedAt == this.updatedAt);
}

class CachedStudentProfilesCompanion
    extends UpdateCompanion<CachedStudentProfile> {
  final Value<int> studentId;
  final Value<String> payloadJson;
  final Value<DateTime> updatedAt;
  const CachedStudentProfilesCompanion({
    this.studentId = const Value.absent(),
    this.payloadJson = const Value.absent(),
    this.updatedAt = const Value.absent(),
  });
  CachedStudentProfilesCompanion.insert({
    this.studentId = const Value.absent(),
    required String payloadJson,
    required DateTime updatedAt,
  }) : payloadJson = Value(payloadJson),
       updatedAt = Value(updatedAt);
  static Insertable<CachedStudentProfile> custom({
    Expression<int>? studentId,
    Expression<String>? payloadJson,
    Expression<DateTime>? updatedAt,
  }) {
    return RawValuesInsertable({
      if (studentId != null) 'student_id': studentId,
      if (payloadJson != null) 'payload_json': payloadJson,
      if (updatedAt != null) 'updated_at': updatedAt,
    });
  }

  CachedStudentProfilesCompanion copyWith({
    Value<int>? studentId,
    Value<String>? payloadJson,
    Value<DateTime>? updatedAt,
  }) {
    return CachedStudentProfilesCompanion(
      studentId: studentId ?? this.studentId,
      payloadJson: payloadJson ?? this.payloadJson,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (studentId.present) {
      map['student_id'] = Variable<int>(studentId.value);
    }
    if (payloadJson.present) {
      map['payload_json'] = Variable<String>(payloadJson.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CachedStudentProfilesCompanion(')
          ..write('studentId: $studentId, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }
}

class $PendingStudentOperationsTable extends PendingStudentOperations
    with TableInfo<$PendingStudentOperationsTable, PendingStudentOperation> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $PendingStudentOperationsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _operationUuidMeta = const VerificationMeta(
    'operationUuid',
  );
  @override
  late final GeneratedColumn<String> operationUuid = GeneratedColumn<String>(
    'operation_uuid',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _operationTypeMeta = const VerificationMeta(
    'operationType',
  );
  @override
  late final GeneratedColumn<String> operationType = GeneratedColumn<String>(
    'operation_type',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _studentIdMeta = const VerificationMeta(
    'studentId',
  );
  @override
  late final GeneratedColumn<int> studentId = GeneratedColumn<int>(
    'student_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _clientStudentUuidMeta = const VerificationMeta(
    'clientStudentUuid',
  );
  @override
  late final GeneratedColumn<String> clientStudentUuid =
      GeneratedColumn<String>(
        'client_student_uuid',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _halaqaIdMeta = const VerificationMeta(
    'halaqaId',
  );
  @override
  late final GeneratedColumn<int> halaqaId = GeneratedColumn<int>(
    'halaqa_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _payloadJsonMeta = const VerificationMeta(
    'payloadJson',
  );
  @override
  late final GeneratedColumn<String> payloadJson = GeneratedColumn<String>(
    'payload_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('pending'),
  );
  static const VerificationMeta _attemptsMeta = const VerificationMeta(
    'attempts',
  );
  @override
  late final GeneratedColumn<int> attempts = GeneratedColumn<int>(
    'attempts',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _lastErrorMeta = const VerificationMeta(
    'lastError',
  );
  @override
  late final GeneratedColumn<String> lastError = GeneratedColumn<String>(
    'last_error',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _serverStudentIdMeta = const VerificationMeta(
    'serverStudentId',
  );
  @override
  late final GeneratedColumn<int> serverStudentId = GeneratedColumn<int>(
    'server_student_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _clientCreatedAtMeta = const VerificationMeta(
    'clientCreatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> clientCreatedAt =
      GeneratedColumn<DateTime>(
        'client_created_at',
        aliasedName,
        false,
        type: DriftSqlType.dateTime,
        requiredDuringInsert: true,
      );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    operationUuid,
    operationType,
    studentId,
    clientStudentUuid,
    halaqaId,
    payloadJson,
    status,
    attempts,
    lastError,
    serverStudentId,
    clientCreatedAt,
    updatedAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'pending_student_operations';
  @override
  VerificationContext validateIntegrity(
    Insertable<PendingStudentOperation> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('operation_uuid')) {
      context.handle(
        _operationUuidMeta,
        operationUuid.isAcceptableOrUnknown(
          data['operation_uuid']!,
          _operationUuidMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operationUuidMeta);
    }
    if (data.containsKey('operation_type')) {
      context.handle(
        _operationTypeMeta,
        operationType.isAcceptableOrUnknown(
          data['operation_type']!,
          _operationTypeMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operationTypeMeta);
    }
    if (data.containsKey('student_id')) {
      context.handle(
        _studentIdMeta,
        studentId.isAcceptableOrUnknown(data['student_id']!, _studentIdMeta),
      );
    }
    if (data.containsKey('client_student_uuid')) {
      context.handle(
        _clientStudentUuidMeta,
        clientStudentUuid.isAcceptableOrUnknown(
          data['client_student_uuid']!,
          _clientStudentUuidMeta,
        ),
      );
    }
    if (data.containsKey('halaqa_id')) {
      context.handle(
        _halaqaIdMeta,
        halaqaId.isAcceptableOrUnknown(data['halaqa_id']!, _halaqaIdMeta),
      );
    } else if (isInserting) {
      context.missing(_halaqaIdMeta);
    }
    if (data.containsKey('payload_json')) {
      context.handle(
        _payloadJsonMeta,
        payloadJson.isAcceptableOrUnknown(
          data['payload_json']!,
          _payloadJsonMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadJsonMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('attempts')) {
      context.handle(
        _attemptsMeta,
        attempts.isAcceptableOrUnknown(data['attempts']!, _attemptsMeta),
      );
    }
    if (data.containsKey('last_error')) {
      context.handle(
        _lastErrorMeta,
        lastError.isAcceptableOrUnknown(data['last_error']!, _lastErrorMeta),
      );
    }
    if (data.containsKey('server_student_id')) {
      context.handle(
        _serverStudentIdMeta,
        serverStudentId.isAcceptableOrUnknown(
          data['server_student_id']!,
          _serverStudentIdMeta,
        ),
      );
    }
    if (data.containsKey('client_created_at')) {
      context.handle(
        _clientCreatedAtMeta,
        clientCreatedAt.isAcceptableOrUnknown(
          data['client_created_at']!,
          _clientCreatedAtMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_clientCreatedAtMeta);
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {operationUuid};
  @override
  PendingStudentOperation map(
    Map<String, dynamic> data, {
    String? tablePrefix,
  }) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return PendingStudentOperation(
      operationUuid: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operation_uuid'],
      )!,
      operationType: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operation_type'],
      )!,
      studentId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}student_id'],
      ),
      clientStudentUuid: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}client_student_uuid'],
      ),
      halaqaId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}halaqa_id'],
      )!,
      payloadJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_json'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      )!,
      attempts: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}attempts'],
      )!,
      lastError: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}last_error'],
      ),
      serverStudentId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}server_student_id'],
      ),
      clientCreatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}client_created_at'],
      )!,
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $PendingStudentOperationsTable createAlias(String alias) {
    return $PendingStudentOperationsTable(attachedDatabase, alias);
  }
}

class PendingStudentOperation extends DataClass
    implements Insertable<PendingStudentOperation> {
  final String operationUuid;
  final String operationType;
  final int? studentId;
  final String? clientStudentUuid;
  final int halaqaId;
  final String payloadJson;
  final String status;
  final int attempts;
  final String? lastError;
  final int? serverStudentId;
  final DateTime clientCreatedAt;
  final DateTime updatedAt;
  const PendingStudentOperation({
    required this.operationUuid,
    required this.operationType,
    this.studentId,
    this.clientStudentUuid,
    required this.halaqaId,
    required this.payloadJson,
    required this.status,
    required this.attempts,
    this.lastError,
    this.serverStudentId,
    required this.clientCreatedAt,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['operation_uuid'] = Variable<String>(operationUuid);
    map['operation_type'] = Variable<String>(operationType);
    if (!nullToAbsent || studentId != null) {
      map['student_id'] = Variable<int>(studentId);
    }
    if (!nullToAbsent || clientStudentUuid != null) {
      map['client_student_uuid'] = Variable<String>(clientStudentUuid);
    }
    map['halaqa_id'] = Variable<int>(halaqaId);
    map['payload_json'] = Variable<String>(payloadJson);
    map['status'] = Variable<String>(status);
    map['attempts'] = Variable<int>(attempts);
    if (!nullToAbsent || lastError != null) {
      map['last_error'] = Variable<String>(lastError);
    }
    if (!nullToAbsent || serverStudentId != null) {
      map['server_student_id'] = Variable<int>(serverStudentId);
    }
    map['client_created_at'] = Variable<DateTime>(clientCreatedAt);
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  PendingStudentOperationsCompanion toCompanion(bool nullToAbsent) {
    return PendingStudentOperationsCompanion(
      operationUuid: Value(operationUuid),
      operationType: Value(operationType),
      studentId: studentId == null && nullToAbsent
          ? const Value.absent()
          : Value(studentId),
      clientStudentUuid: clientStudentUuid == null && nullToAbsent
          ? const Value.absent()
          : Value(clientStudentUuid),
      halaqaId: Value(halaqaId),
      payloadJson: Value(payloadJson),
      status: Value(status),
      attempts: Value(attempts),
      lastError: lastError == null && nullToAbsent
          ? const Value.absent()
          : Value(lastError),
      serverStudentId: serverStudentId == null && nullToAbsent
          ? const Value.absent()
          : Value(serverStudentId),
      clientCreatedAt: Value(clientCreatedAt),
      updatedAt: Value(updatedAt),
    );
  }

  factory PendingStudentOperation.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return PendingStudentOperation(
      operationUuid: serializer.fromJson<String>(json['operationUuid']),
      operationType: serializer.fromJson<String>(json['operationType']),
      studentId: serializer.fromJson<int?>(json['studentId']),
      clientStudentUuid: serializer.fromJson<String?>(
        json['clientStudentUuid'],
      ),
      halaqaId: serializer.fromJson<int>(json['halaqaId']),
      payloadJson: serializer.fromJson<String>(json['payloadJson']),
      status: serializer.fromJson<String>(json['status']),
      attempts: serializer.fromJson<int>(json['attempts']),
      lastError: serializer.fromJson<String?>(json['lastError']),
      serverStudentId: serializer.fromJson<int?>(json['serverStudentId']),
      clientCreatedAt: serializer.fromJson<DateTime>(json['clientCreatedAt']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'operationUuid': serializer.toJson<String>(operationUuid),
      'operationType': serializer.toJson<String>(operationType),
      'studentId': serializer.toJson<int?>(studentId),
      'clientStudentUuid': serializer.toJson<String?>(clientStudentUuid),
      'halaqaId': serializer.toJson<int>(halaqaId),
      'payloadJson': serializer.toJson<String>(payloadJson),
      'status': serializer.toJson<String>(status),
      'attempts': serializer.toJson<int>(attempts),
      'lastError': serializer.toJson<String?>(lastError),
      'serverStudentId': serializer.toJson<int?>(serverStudentId),
      'clientCreatedAt': serializer.toJson<DateTime>(clientCreatedAt),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  PendingStudentOperation copyWith({
    String? operationUuid,
    String? operationType,
    Value<int?> studentId = const Value.absent(),
    Value<String?> clientStudentUuid = const Value.absent(),
    int? halaqaId,
    String? payloadJson,
    String? status,
    int? attempts,
    Value<String?> lastError = const Value.absent(),
    Value<int?> serverStudentId = const Value.absent(),
    DateTime? clientCreatedAt,
    DateTime? updatedAt,
  }) => PendingStudentOperation(
    operationUuid: operationUuid ?? this.operationUuid,
    operationType: operationType ?? this.operationType,
    studentId: studentId.present ? studentId.value : this.studentId,
    clientStudentUuid: clientStudentUuid.present
        ? clientStudentUuid.value
        : this.clientStudentUuid,
    halaqaId: halaqaId ?? this.halaqaId,
    payloadJson: payloadJson ?? this.payloadJson,
    status: status ?? this.status,
    attempts: attempts ?? this.attempts,
    lastError: lastError.present ? lastError.value : this.lastError,
    serverStudentId: serverStudentId.present
        ? serverStudentId.value
        : this.serverStudentId,
    clientCreatedAt: clientCreatedAt ?? this.clientCreatedAt,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  PendingStudentOperation copyWithCompanion(
    PendingStudentOperationsCompanion data,
  ) {
    return PendingStudentOperation(
      operationUuid: data.operationUuid.present
          ? data.operationUuid.value
          : this.operationUuid,
      operationType: data.operationType.present
          ? data.operationType.value
          : this.operationType,
      studentId: data.studentId.present ? data.studentId.value : this.studentId,
      clientStudentUuid: data.clientStudentUuid.present
          ? data.clientStudentUuid.value
          : this.clientStudentUuid,
      halaqaId: data.halaqaId.present ? data.halaqaId.value : this.halaqaId,
      payloadJson: data.payloadJson.present
          ? data.payloadJson.value
          : this.payloadJson,
      status: data.status.present ? data.status.value : this.status,
      attempts: data.attempts.present ? data.attempts.value : this.attempts,
      lastError: data.lastError.present ? data.lastError.value : this.lastError,
      serverStudentId: data.serverStudentId.present
          ? data.serverStudentId.value
          : this.serverStudentId,
      clientCreatedAt: data.clientCreatedAt.present
          ? data.clientCreatedAt.value
          : this.clientCreatedAt,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('PendingStudentOperation(')
          ..write('operationUuid: $operationUuid, ')
          ..write('operationType: $operationType, ')
          ..write('studentId: $studentId, ')
          ..write('clientStudentUuid: $clientStudentUuid, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('status: $status, ')
          ..write('attempts: $attempts, ')
          ..write('lastError: $lastError, ')
          ..write('serverStudentId: $serverStudentId, ')
          ..write('clientCreatedAt: $clientCreatedAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    operationUuid,
    operationType,
    studentId,
    clientStudentUuid,
    halaqaId,
    payloadJson,
    status,
    attempts,
    lastError,
    serverStudentId,
    clientCreatedAt,
    updatedAt,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is PendingStudentOperation &&
          other.operationUuid == this.operationUuid &&
          other.operationType == this.operationType &&
          other.studentId == this.studentId &&
          other.clientStudentUuid == this.clientStudentUuid &&
          other.halaqaId == this.halaqaId &&
          other.payloadJson == this.payloadJson &&
          other.status == this.status &&
          other.attempts == this.attempts &&
          other.lastError == this.lastError &&
          other.serverStudentId == this.serverStudentId &&
          other.clientCreatedAt == this.clientCreatedAt &&
          other.updatedAt == this.updatedAt);
}

class PendingStudentOperationsCompanion
    extends UpdateCompanion<PendingStudentOperation> {
  final Value<String> operationUuid;
  final Value<String> operationType;
  final Value<int?> studentId;
  final Value<String?> clientStudentUuid;
  final Value<int> halaqaId;
  final Value<String> payloadJson;
  final Value<String> status;
  final Value<int> attempts;
  final Value<String?> lastError;
  final Value<int?> serverStudentId;
  final Value<DateTime> clientCreatedAt;
  final Value<DateTime> updatedAt;
  final Value<int> rowid;
  const PendingStudentOperationsCompanion({
    this.operationUuid = const Value.absent(),
    this.operationType = const Value.absent(),
    this.studentId = const Value.absent(),
    this.clientStudentUuid = const Value.absent(),
    this.halaqaId = const Value.absent(),
    this.payloadJson = const Value.absent(),
    this.status = const Value.absent(),
    this.attempts = const Value.absent(),
    this.lastError = const Value.absent(),
    this.serverStudentId = const Value.absent(),
    this.clientCreatedAt = const Value.absent(),
    this.updatedAt = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  PendingStudentOperationsCompanion.insert({
    required String operationUuid,
    required String operationType,
    this.studentId = const Value.absent(),
    this.clientStudentUuid = const Value.absent(),
    required int halaqaId,
    required String payloadJson,
    this.status = const Value.absent(),
    this.attempts = const Value.absent(),
    this.lastError = const Value.absent(),
    this.serverStudentId = const Value.absent(),
    required DateTime clientCreatedAt,
    required DateTime updatedAt,
    this.rowid = const Value.absent(),
  }) : operationUuid = Value(operationUuid),
       operationType = Value(operationType),
       halaqaId = Value(halaqaId),
       payloadJson = Value(payloadJson),
       clientCreatedAt = Value(clientCreatedAt),
       updatedAt = Value(updatedAt);
  static Insertable<PendingStudentOperation> custom({
    Expression<String>? operationUuid,
    Expression<String>? operationType,
    Expression<int>? studentId,
    Expression<String>? clientStudentUuid,
    Expression<int>? halaqaId,
    Expression<String>? payloadJson,
    Expression<String>? status,
    Expression<int>? attempts,
    Expression<String>? lastError,
    Expression<int>? serverStudentId,
    Expression<DateTime>? clientCreatedAt,
    Expression<DateTime>? updatedAt,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (operationUuid != null) 'operation_uuid': operationUuid,
      if (operationType != null) 'operation_type': operationType,
      if (studentId != null) 'student_id': studentId,
      if (clientStudentUuid != null) 'client_student_uuid': clientStudentUuid,
      if (halaqaId != null) 'halaqa_id': halaqaId,
      if (payloadJson != null) 'payload_json': payloadJson,
      if (status != null) 'status': status,
      if (attempts != null) 'attempts': attempts,
      if (lastError != null) 'last_error': lastError,
      if (serverStudentId != null) 'server_student_id': serverStudentId,
      if (clientCreatedAt != null) 'client_created_at': clientCreatedAt,
      if (updatedAt != null) 'updated_at': updatedAt,
      if (rowid != null) 'rowid': rowid,
    });
  }

  PendingStudentOperationsCompanion copyWith({
    Value<String>? operationUuid,
    Value<String>? operationType,
    Value<int?>? studentId,
    Value<String?>? clientStudentUuid,
    Value<int>? halaqaId,
    Value<String>? payloadJson,
    Value<String>? status,
    Value<int>? attempts,
    Value<String?>? lastError,
    Value<int?>? serverStudentId,
    Value<DateTime>? clientCreatedAt,
    Value<DateTime>? updatedAt,
    Value<int>? rowid,
  }) {
    return PendingStudentOperationsCompanion(
      operationUuid: operationUuid ?? this.operationUuid,
      operationType: operationType ?? this.operationType,
      studentId: studentId ?? this.studentId,
      clientStudentUuid: clientStudentUuid ?? this.clientStudentUuid,
      halaqaId: halaqaId ?? this.halaqaId,
      payloadJson: payloadJson ?? this.payloadJson,
      status: status ?? this.status,
      attempts: attempts ?? this.attempts,
      lastError: lastError ?? this.lastError,
      serverStudentId: serverStudentId ?? this.serverStudentId,
      clientCreatedAt: clientCreatedAt ?? this.clientCreatedAt,
      updatedAt: updatedAt ?? this.updatedAt,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (operationUuid.present) {
      map['operation_uuid'] = Variable<String>(operationUuid.value);
    }
    if (operationType.present) {
      map['operation_type'] = Variable<String>(operationType.value);
    }
    if (studentId.present) {
      map['student_id'] = Variable<int>(studentId.value);
    }
    if (clientStudentUuid.present) {
      map['client_student_uuid'] = Variable<String>(clientStudentUuid.value);
    }
    if (halaqaId.present) {
      map['halaqa_id'] = Variable<int>(halaqaId.value);
    }
    if (payloadJson.present) {
      map['payload_json'] = Variable<String>(payloadJson.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (attempts.present) {
      map['attempts'] = Variable<int>(attempts.value);
    }
    if (lastError.present) {
      map['last_error'] = Variable<String>(lastError.value);
    }
    if (serverStudentId.present) {
      map['server_student_id'] = Variable<int>(serverStudentId.value);
    }
    if (clientCreatedAt.present) {
      map['client_created_at'] = Variable<DateTime>(clientCreatedAt.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('PendingStudentOperationsCompanion(')
          ..write('operationUuid: $operationUuid, ')
          ..write('operationType: $operationType, ')
          ..write('studentId: $studentId, ')
          ..write('clientStudentUuid: $clientStudentUuid, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('status: $status, ')
          ..write('attempts: $attempts, ')
          ..write('lastError: $lastError, ')
          ..write('serverStudentId: $serverStudentId, ')
          ..write('clientCreatedAt: $clientCreatedAt, ')
          ..write('updatedAt: $updatedAt, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $LocalStudentDraftsTable extends LocalStudentDrafts
    with TableInfo<$LocalStudentDraftsTable, LocalStudentDraft> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalStudentDraftsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _clientUuidMeta = const VerificationMeta(
    'clientUuid',
  );
  @override
  late final GeneratedColumn<String> clientUuid = GeneratedColumn<String>(
    'client_uuid',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _halaqaIdMeta = const VerificationMeta(
    'halaqaId',
  );
  @override
  late final GeneratedColumn<int> halaqaId = GeneratedColumn<int>(
    'halaqa_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _payloadJsonMeta = const VerificationMeta(
    'payloadJson',
  );
  @override
  late final GeneratedColumn<String> payloadJson = GeneratedColumn<String>(
    'payload_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _createdAtMeta = const VerificationMeta(
    'createdAt',
  );
  @override
  late final GeneratedColumn<DateTime> createdAt = GeneratedColumn<DateTime>(
    'created_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    clientUuid,
    halaqaId,
    payloadJson,
    createdAt,
    updatedAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_student_drafts';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalStudentDraft> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('client_uuid')) {
      context.handle(
        _clientUuidMeta,
        clientUuid.isAcceptableOrUnknown(data['client_uuid']!, _clientUuidMeta),
      );
    } else if (isInserting) {
      context.missing(_clientUuidMeta);
    }
    if (data.containsKey('halaqa_id')) {
      context.handle(
        _halaqaIdMeta,
        halaqaId.isAcceptableOrUnknown(data['halaqa_id']!, _halaqaIdMeta),
      );
    } else if (isInserting) {
      context.missing(_halaqaIdMeta);
    }
    if (data.containsKey('payload_json')) {
      context.handle(
        _payloadJsonMeta,
        payloadJson.isAcceptableOrUnknown(
          data['payload_json']!,
          _payloadJsonMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadJsonMeta);
    }
    if (data.containsKey('created_at')) {
      context.handle(
        _createdAtMeta,
        createdAt.isAcceptableOrUnknown(data['created_at']!, _createdAtMeta),
      );
    } else if (isInserting) {
      context.missing(_createdAtMeta);
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {clientUuid};
  @override
  LocalStudentDraft map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalStudentDraft(
      clientUuid: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}client_uuid'],
      )!,
      halaqaId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}halaqa_id'],
      )!,
      payloadJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_json'],
      )!,
      createdAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}created_at'],
      )!,
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $LocalStudentDraftsTable createAlias(String alias) {
    return $LocalStudentDraftsTable(attachedDatabase, alias);
  }
}

class LocalStudentDraft extends DataClass
    implements Insertable<LocalStudentDraft> {
  final String clientUuid;
  final int halaqaId;
  final String payloadJson;
  final DateTime createdAt;
  final DateTime updatedAt;
  const LocalStudentDraft({
    required this.clientUuid,
    required this.halaqaId,
    required this.payloadJson,
    required this.createdAt,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['client_uuid'] = Variable<String>(clientUuid);
    map['halaqa_id'] = Variable<int>(halaqaId);
    map['payload_json'] = Variable<String>(payloadJson);
    map['created_at'] = Variable<DateTime>(createdAt);
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  LocalStudentDraftsCompanion toCompanion(bool nullToAbsent) {
    return LocalStudentDraftsCompanion(
      clientUuid: Value(clientUuid),
      halaqaId: Value(halaqaId),
      payloadJson: Value(payloadJson),
      createdAt: Value(createdAt),
      updatedAt: Value(updatedAt),
    );
  }

  factory LocalStudentDraft.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalStudentDraft(
      clientUuid: serializer.fromJson<String>(json['clientUuid']),
      halaqaId: serializer.fromJson<int>(json['halaqaId']),
      payloadJson: serializer.fromJson<String>(json['payloadJson']),
      createdAt: serializer.fromJson<DateTime>(json['createdAt']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'clientUuid': serializer.toJson<String>(clientUuid),
      'halaqaId': serializer.toJson<int>(halaqaId),
      'payloadJson': serializer.toJson<String>(payloadJson),
      'createdAt': serializer.toJson<DateTime>(createdAt),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  LocalStudentDraft copyWith({
    String? clientUuid,
    int? halaqaId,
    String? payloadJson,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) => LocalStudentDraft(
    clientUuid: clientUuid ?? this.clientUuid,
    halaqaId: halaqaId ?? this.halaqaId,
    payloadJson: payloadJson ?? this.payloadJson,
    createdAt: createdAt ?? this.createdAt,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  LocalStudentDraft copyWithCompanion(LocalStudentDraftsCompanion data) {
    return LocalStudentDraft(
      clientUuid: data.clientUuid.present
          ? data.clientUuid.value
          : this.clientUuid,
      halaqaId: data.halaqaId.present ? data.halaqaId.value : this.halaqaId,
      payloadJson: data.payloadJson.present
          ? data.payloadJson.value
          : this.payloadJson,
      createdAt: data.createdAt.present ? data.createdAt.value : this.createdAt,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalStudentDraft(')
          ..write('clientUuid: $clientUuid, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('createdAt: $createdAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(clientUuid, halaqaId, payloadJson, createdAt, updatedAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalStudentDraft &&
          other.clientUuid == this.clientUuid &&
          other.halaqaId == this.halaqaId &&
          other.payloadJson == this.payloadJson &&
          other.createdAt == this.createdAt &&
          other.updatedAt == this.updatedAt);
}

class LocalStudentDraftsCompanion extends UpdateCompanion<LocalStudentDraft> {
  final Value<String> clientUuid;
  final Value<int> halaqaId;
  final Value<String> payloadJson;
  final Value<DateTime> createdAt;
  final Value<DateTime> updatedAt;
  final Value<int> rowid;
  const LocalStudentDraftsCompanion({
    this.clientUuid = const Value.absent(),
    this.halaqaId = const Value.absent(),
    this.payloadJson = const Value.absent(),
    this.createdAt = const Value.absent(),
    this.updatedAt = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalStudentDraftsCompanion.insert({
    required String clientUuid,
    required int halaqaId,
    required String payloadJson,
    required DateTime createdAt,
    required DateTime updatedAt,
    this.rowid = const Value.absent(),
  }) : clientUuid = Value(clientUuid),
       halaqaId = Value(halaqaId),
       payloadJson = Value(payloadJson),
       createdAt = Value(createdAt),
       updatedAt = Value(updatedAt);
  static Insertable<LocalStudentDraft> custom({
    Expression<String>? clientUuid,
    Expression<int>? halaqaId,
    Expression<String>? payloadJson,
    Expression<DateTime>? createdAt,
    Expression<DateTime>? updatedAt,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (clientUuid != null) 'client_uuid': clientUuid,
      if (halaqaId != null) 'halaqa_id': halaqaId,
      if (payloadJson != null) 'payload_json': payloadJson,
      if (createdAt != null) 'created_at': createdAt,
      if (updatedAt != null) 'updated_at': updatedAt,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalStudentDraftsCompanion copyWith({
    Value<String>? clientUuid,
    Value<int>? halaqaId,
    Value<String>? payloadJson,
    Value<DateTime>? createdAt,
    Value<DateTime>? updatedAt,
    Value<int>? rowid,
  }) {
    return LocalStudentDraftsCompanion(
      clientUuid: clientUuid ?? this.clientUuid,
      halaqaId: halaqaId ?? this.halaqaId,
      payloadJson: payloadJson ?? this.payloadJson,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (clientUuid.present) {
      map['client_uuid'] = Variable<String>(clientUuid.value);
    }
    if (halaqaId.present) {
      map['halaqa_id'] = Variable<int>(halaqaId.value);
    }
    if (payloadJson.present) {
      map['payload_json'] = Variable<String>(payloadJson.value);
    }
    if (createdAt.present) {
      map['created_at'] = Variable<DateTime>(createdAt.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalStudentDraftsCompanion(')
          ..write('clientUuid: $clientUuid, ')
          ..write('halaqaId: $halaqaId, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('createdAt: $createdAt, ')
          ..write('updatedAt: $updatedAt, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

abstract class _$AppDatabase extends GeneratedDatabase {
  _$AppDatabase(QueryExecutor e) : super(e);
  $AppDatabaseManager get managers => $AppDatabaseManager(this);
  late final $AppSettingsTable appSettings = $AppSettingsTable(this);
  late final $CachedHalaqasTable cachedHalaqas = $CachedHalaqasTable(this);
  late final $CachedStudentsTable cachedStudents = $CachedStudentsTable(this);
  late final $CachedSurahsTable cachedSurahs = $CachedSurahsTable(this);
  late final $CachedAyahsTable cachedAyahs = $CachedAyahsTable(this);
  late final $PendingDailyRecordsTable pendingDailyRecords =
      $PendingDailyRecordsTable(this);
  late final $CachedTeacherProfilesTable cachedTeacherProfiles =
      $CachedTeacherProfilesTable(this);
  late final $CachedStudentProfilesTable cachedStudentProfiles =
      $CachedStudentProfilesTable(this);
  late final $PendingStudentOperationsTable pendingStudentOperations =
      $PendingStudentOperationsTable(this);
  late final $LocalStudentDraftsTable localStudentDrafts =
      $LocalStudentDraftsTable(this);
  @override
  Iterable<TableInfo<Table, Object?>> get allTables =>
      allSchemaEntities.whereType<TableInfo<Table, Object?>>();
  @override
  List<DatabaseSchemaEntity> get allSchemaEntities => [
    appSettings,
    cachedHalaqas,
    cachedStudents,
    cachedSurahs,
    cachedAyahs,
    pendingDailyRecords,
    cachedTeacherProfiles,
    cachedStudentProfiles,
    pendingStudentOperations,
    localStudentDrafts,
  ];
}

typedef $$AppSettingsTableCreateCompanionBuilder =
    AppSettingsCompanion Function({
      required String key,
      Value<String?> value,
      Value<int> rowid,
    });
typedef $$AppSettingsTableUpdateCompanionBuilder =
    AppSettingsCompanion Function({
      Value<String> key,
      Value<String?> value,
      Value<int> rowid,
    });

class $$AppSettingsTableFilterComposer
    extends Composer<_$AppDatabase, $AppSettingsTable> {
  $$AppSettingsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get key => $composableBuilder(
    column: $table.key,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get value => $composableBuilder(
    column: $table.value,
    builder: (column) => ColumnFilters(column),
  );
}

class $$AppSettingsTableOrderingComposer
    extends Composer<_$AppDatabase, $AppSettingsTable> {
  $$AppSettingsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get key => $composableBuilder(
    column: $table.key,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get value => $composableBuilder(
    column: $table.value,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$AppSettingsTableAnnotationComposer
    extends Composer<_$AppDatabase, $AppSettingsTable> {
  $$AppSettingsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get key =>
      $composableBuilder(column: $table.key, builder: (column) => column);

  GeneratedColumn<String> get value =>
      $composableBuilder(column: $table.value, builder: (column) => column);
}

class $$AppSettingsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $AppSettingsTable,
          AppSetting,
          $$AppSettingsTableFilterComposer,
          $$AppSettingsTableOrderingComposer,
          $$AppSettingsTableAnnotationComposer,
          $$AppSettingsTableCreateCompanionBuilder,
          $$AppSettingsTableUpdateCompanionBuilder,
          (
            AppSetting,
            BaseReferences<_$AppDatabase, $AppSettingsTable, AppSetting>,
          ),
          AppSetting,
          PrefetchHooks Function()
        > {
  $$AppSettingsTableTableManager(_$AppDatabase db, $AppSettingsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$AppSettingsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$AppSettingsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$AppSettingsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> key = const Value.absent(),
                Value<String?> value = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => AppSettingsCompanion(key: key, value: value, rowid: rowid),
          createCompanionCallback:
              ({
                required String key,
                Value<String?> value = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => AppSettingsCompanion.insert(
                key: key,
                value: value,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$AppSettingsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $AppSettingsTable,
      AppSetting,
      $$AppSettingsTableFilterComposer,
      $$AppSettingsTableOrderingComposer,
      $$AppSettingsTableAnnotationComposer,
      $$AppSettingsTableCreateCompanionBuilder,
      $$AppSettingsTableUpdateCompanionBuilder,
      (
        AppSetting,
        BaseReferences<_$AppDatabase, $AppSettingsTable, AppSetting>,
      ),
      AppSetting,
      PrefetchHooks Function()
    >;
typedef $$CachedHalaqasTableCreateCompanionBuilder =
    CachedHalaqasCompanion Function({
      Value<int> id,
      required String name,
      Value<String?> code,
      required int centerId,
      required String centerName,
    });
typedef $$CachedHalaqasTableUpdateCompanionBuilder =
    CachedHalaqasCompanion Function({
      Value<int> id,
      Value<String> name,
      Value<String?> code,
      Value<int> centerId,
      Value<String> centerName,
    });

class $$CachedHalaqasTableFilterComposer
    extends Composer<_$AppDatabase, $CachedHalaqasTable> {
  $$CachedHalaqasTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get code => $composableBuilder(
    column: $table.code,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get centerId => $composableBuilder(
    column: $table.centerId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get centerName => $composableBuilder(
    column: $table.centerName,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CachedHalaqasTableOrderingComposer
    extends Composer<_$AppDatabase, $CachedHalaqasTable> {
  $$CachedHalaqasTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get code => $composableBuilder(
    column: $table.code,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get centerId => $composableBuilder(
    column: $table.centerId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get centerName => $composableBuilder(
    column: $table.centerName,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CachedHalaqasTableAnnotationComposer
    extends Composer<_$AppDatabase, $CachedHalaqasTable> {
  $$CachedHalaqasTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get code =>
      $composableBuilder(column: $table.code, builder: (column) => column);

  GeneratedColumn<int> get centerId =>
      $composableBuilder(column: $table.centerId, builder: (column) => column);

  GeneratedColumn<String> get centerName => $composableBuilder(
    column: $table.centerName,
    builder: (column) => column,
  );
}

class $$CachedHalaqasTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CachedHalaqasTable,
          CachedHalaqa,
          $$CachedHalaqasTableFilterComposer,
          $$CachedHalaqasTableOrderingComposer,
          $$CachedHalaqasTableAnnotationComposer,
          $$CachedHalaqasTableCreateCompanionBuilder,
          $$CachedHalaqasTableUpdateCompanionBuilder,
          (
            CachedHalaqa,
            BaseReferences<_$AppDatabase, $CachedHalaqasTable, CachedHalaqa>,
          ),
          CachedHalaqa,
          PrefetchHooks Function()
        > {
  $$CachedHalaqasTableTableManager(_$AppDatabase db, $CachedHalaqasTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CachedHalaqasTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CachedHalaqasTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CachedHalaqasTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String?> code = const Value.absent(),
                Value<int> centerId = const Value.absent(),
                Value<String> centerName = const Value.absent(),
              }) => CachedHalaqasCompanion(
                id: id,
                name: name,
                code: code,
                centerId: centerId,
                centerName: centerName,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required String name,
                Value<String?> code = const Value.absent(),
                required int centerId,
                required String centerName,
              }) => CachedHalaqasCompanion.insert(
                id: id,
                name: name,
                code: code,
                centerId: centerId,
                centerName: centerName,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CachedHalaqasTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CachedHalaqasTable,
      CachedHalaqa,
      $$CachedHalaqasTableFilterComposer,
      $$CachedHalaqasTableOrderingComposer,
      $$CachedHalaqasTableAnnotationComposer,
      $$CachedHalaqasTableCreateCompanionBuilder,
      $$CachedHalaqasTableUpdateCompanionBuilder,
      (
        CachedHalaqa,
        BaseReferences<_$AppDatabase, $CachedHalaqasTable, CachedHalaqa>,
      ),
      CachedHalaqa,
      PrefetchHooks Function()
    >;
typedef $$CachedStudentsTableCreateCompanionBuilder =
    CachedStudentsCompanion Function({
      Value<int> id,
      required int halaqaId,
      required String studentNumber,
      required String fullName,
      Value<bool> recordedToday,
      Value<int?> dailyRecordId,
    });
typedef $$CachedStudentsTableUpdateCompanionBuilder =
    CachedStudentsCompanion Function({
      Value<int> id,
      Value<int> halaqaId,
      Value<String> studentNumber,
      Value<String> fullName,
      Value<bool> recordedToday,
      Value<int?> dailyRecordId,
    });

class $$CachedStudentsTableFilterComposer
    extends Composer<_$AppDatabase, $CachedStudentsTable> {
  $$CachedStudentsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get studentNumber => $composableBuilder(
    column: $table.studentNumber,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get fullName => $composableBuilder(
    column: $table.fullName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get recordedToday => $composableBuilder(
    column: $table.recordedToday,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get dailyRecordId => $composableBuilder(
    column: $table.dailyRecordId,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CachedStudentsTableOrderingComposer
    extends Composer<_$AppDatabase, $CachedStudentsTable> {
  $$CachedStudentsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get studentNumber => $composableBuilder(
    column: $table.studentNumber,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get fullName => $composableBuilder(
    column: $table.fullName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get recordedToday => $composableBuilder(
    column: $table.recordedToday,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get dailyRecordId => $composableBuilder(
    column: $table.dailyRecordId,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CachedStudentsTableAnnotationComposer
    extends Composer<_$AppDatabase, $CachedStudentsTable> {
  $$CachedStudentsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get halaqaId =>
      $composableBuilder(column: $table.halaqaId, builder: (column) => column);

  GeneratedColumn<String> get studentNumber => $composableBuilder(
    column: $table.studentNumber,
    builder: (column) => column,
  );

  GeneratedColumn<String> get fullName =>
      $composableBuilder(column: $table.fullName, builder: (column) => column);

  GeneratedColumn<bool> get recordedToday => $composableBuilder(
    column: $table.recordedToday,
    builder: (column) => column,
  );

  GeneratedColumn<int> get dailyRecordId => $composableBuilder(
    column: $table.dailyRecordId,
    builder: (column) => column,
  );
}

class $$CachedStudentsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CachedStudentsTable,
          CachedStudent,
          $$CachedStudentsTableFilterComposer,
          $$CachedStudentsTableOrderingComposer,
          $$CachedStudentsTableAnnotationComposer,
          $$CachedStudentsTableCreateCompanionBuilder,
          $$CachedStudentsTableUpdateCompanionBuilder,
          (
            CachedStudent,
            BaseReferences<_$AppDatabase, $CachedStudentsTable, CachedStudent>,
          ),
          CachedStudent,
          PrefetchHooks Function()
        > {
  $$CachedStudentsTableTableManager(
    _$AppDatabase db,
    $CachedStudentsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CachedStudentsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CachedStudentsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CachedStudentsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<int> halaqaId = const Value.absent(),
                Value<String> studentNumber = const Value.absent(),
                Value<String> fullName = const Value.absent(),
                Value<bool> recordedToday = const Value.absent(),
                Value<int?> dailyRecordId = const Value.absent(),
              }) => CachedStudentsCompanion(
                id: id,
                halaqaId: halaqaId,
                studentNumber: studentNumber,
                fullName: fullName,
                recordedToday: recordedToday,
                dailyRecordId: dailyRecordId,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required int halaqaId,
                required String studentNumber,
                required String fullName,
                Value<bool> recordedToday = const Value.absent(),
                Value<int?> dailyRecordId = const Value.absent(),
              }) => CachedStudentsCompanion.insert(
                id: id,
                halaqaId: halaqaId,
                studentNumber: studentNumber,
                fullName: fullName,
                recordedToday: recordedToday,
                dailyRecordId: dailyRecordId,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CachedStudentsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CachedStudentsTable,
      CachedStudent,
      $$CachedStudentsTableFilterComposer,
      $$CachedStudentsTableOrderingComposer,
      $$CachedStudentsTableAnnotationComposer,
      $$CachedStudentsTableCreateCompanionBuilder,
      $$CachedStudentsTableUpdateCompanionBuilder,
      (
        CachedStudent,
        BaseReferences<_$AppDatabase, $CachedStudentsTable, CachedStudent>,
      ),
      CachedStudent,
      PrefetchHooks Function()
    >;
typedef $$CachedSurahsTableCreateCompanionBuilder =
    CachedSurahsCompanion Function({
      Value<int> id,
      required String nameArabic,
      required int versesCount,
    });
typedef $$CachedSurahsTableUpdateCompanionBuilder =
    CachedSurahsCompanion Function({
      Value<int> id,
      Value<String> nameArabic,
      Value<int> versesCount,
    });

class $$CachedSurahsTableFilterComposer
    extends Composer<_$AppDatabase, $CachedSurahsTable> {
  $$CachedSurahsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nameArabic => $composableBuilder(
    column: $table.nameArabic,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get versesCount => $composableBuilder(
    column: $table.versesCount,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CachedSurahsTableOrderingComposer
    extends Composer<_$AppDatabase, $CachedSurahsTable> {
  $$CachedSurahsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nameArabic => $composableBuilder(
    column: $table.nameArabic,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get versesCount => $composableBuilder(
    column: $table.versesCount,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CachedSurahsTableAnnotationComposer
    extends Composer<_$AppDatabase, $CachedSurahsTable> {
  $$CachedSurahsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get nameArabic => $composableBuilder(
    column: $table.nameArabic,
    builder: (column) => column,
  );

  GeneratedColumn<int> get versesCount => $composableBuilder(
    column: $table.versesCount,
    builder: (column) => column,
  );
}

class $$CachedSurahsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CachedSurahsTable,
          CachedSurah,
          $$CachedSurahsTableFilterComposer,
          $$CachedSurahsTableOrderingComposer,
          $$CachedSurahsTableAnnotationComposer,
          $$CachedSurahsTableCreateCompanionBuilder,
          $$CachedSurahsTableUpdateCompanionBuilder,
          (
            CachedSurah,
            BaseReferences<_$AppDatabase, $CachedSurahsTable, CachedSurah>,
          ),
          CachedSurah,
          PrefetchHooks Function()
        > {
  $$CachedSurahsTableTableManager(_$AppDatabase db, $CachedSurahsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CachedSurahsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CachedSurahsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CachedSurahsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<String> nameArabic = const Value.absent(),
                Value<int> versesCount = const Value.absent(),
              }) => CachedSurahsCompanion(
                id: id,
                nameArabic: nameArabic,
                versesCount: versesCount,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required String nameArabic,
                required int versesCount,
              }) => CachedSurahsCompanion.insert(
                id: id,
                nameArabic: nameArabic,
                versesCount: versesCount,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CachedSurahsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CachedSurahsTable,
      CachedSurah,
      $$CachedSurahsTableFilterComposer,
      $$CachedSurahsTableOrderingComposer,
      $$CachedSurahsTableAnnotationComposer,
      $$CachedSurahsTableCreateCompanionBuilder,
      $$CachedSurahsTableUpdateCompanionBuilder,
      (
        CachedSurah,
        BaseReferences<_$AppDatabase, $CachedSurahsTable, CachedSurah>,
      ),
      CachedSurah,
      PrefetchHooks Function()
    >;
typedef $$CachedAyahsTableCreateCompanionBuilder =
    CachedAyahsCompanion Function({
      Value<int> id,
      required int surahId,
      required int ayahNumber,
      required int globalOrder,
      Value<int?> juz,
    });
typedef $$CachedAyahsTableUpdateCompanionBuilder =
    CachedAyahsCompanion Function({
      Value<int> id,
      Value<int> surahId,
      Value<int> ayahNumber,
      Value<int> globalOrder,
      Value<int?> juz,
    });

class $$CachedAyahsTableFilterComposer
    extends Composer<_$AppDatabase, $CachedAyahsTable> {
  $$CachedAyahsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get surahId => $composableBuilder(
    column: $table.surahId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get ayahNumber => $composableBuilder(
    column: $table.ayahNumber,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get globalOrder => $composableBuilder(
    column: $table.globalOrder,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get juz => $composableBuilder(
    column: $table.juz,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CachedAyahsTableOrderingComposer
    extends Composer<_$AppDatabase, $CachedAyahsTable> {
  $$CachedAyahsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get surahId => $composableBuilder(
    column: $table.surahId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get ayahNumber => $composableBuilder(
    column: $table.ayahNumber,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get globalOrder => $composableBuilder(
    column: $table.globalOrder,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get juz => $composableBuilder(
    column: $table.juz,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CachedAyahsTableAnnotationComposer
    extends Composer<_$AppDatabase, $CachedAyahsTable> {
  $$CachedAyahsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get surahId =>
      $composableBuilder(column: $table.surahId, builder: (column) => column);

  GeneratedColumn<int> get ayahNumber => $composableBuilder(
    column: $table.ayahNumber,
    builder: (column) => column,
  );

  GeneratedColumn<int> get globalOrder => $composableBuilder(
    column: $table.globalOrder,
    builder: (column) => column,
  );

  GeneratedColumn<int> get juz =>
      $composableBuilder(column: $table.juz, builder: (column) => column);
}

class $$CachedAyahsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CachedAyahsTable,
          CachedAyah,
          $$CachedAyahsTableFilterComposer,
          $$CachedAyahsTableOrderingComposer,
          $$CachedAyahsTableAnnotationComposer,
          $$CachedAyahsTableCreateCompanionBuilder,
          $$CachedAyahsTableUpdateCompanionBuilder,
          (
            CachedAyah,
            BaseReferences<_$AppDatabase, $CachedAyahsTable, CachedAyah>,
          ),
          CachedAyah,
          PrefetchHooks Function()
        > {
  $$CachedAyahsTableTableManager(_$AppDatabase db, $CachedAyahsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CachedAyahsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CachedAyahsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CachedAyahsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<int> surahId = const Value.absent(),
                Value<int> ayahNumber = const Value.absent(),
                Value<int> globalOrder = const Value.absent(),
                Value<int?> juz = const Value.absent(),
              }) => CachedAyahsCompanion(
                id: id,
                surahId: surahId,
                ayahNumber: ayahNumber,
                globalOrder: globalOrder,
                juz: juz,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required int surahId,
                required int ayahNumber,
                required int globalOrder,
                Value<int?> juz = const Value.absent(),
              }) => CachedAyahsCompanion.insert(
                id: id,
                surahId: surahId,
                ayahNumber: ayahNumber,
                globalOrder: globalOrder,
                juz: juz,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CachedAyahsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CachedAyahsTable,
      CachedAyah,
      $$CachedAyahsTableFilterComposer,
      $$CachedAyahsTableOrderingComposer,
      $$CachedAyahsTableAnnotationComposer,
      $$CachedAyahsTableCreateCompanionBuilder,
      $$CachedAyahsTableUpdateCompanionBuilder,
      (
        CachedAyah,
        BaseReferences<_$AppDatabase, $CachedAyahsTable, CachedAyah>,
      ),
      CachedAyah,
      PrefetchHooks Function()
    >;
typedef $$PendingDailyRecordsTableCreateCompanionBuilder =
    PendingDailyRecordsCompanion Function({
      required String operationUuid,
      required int studentId,
      Value<String?> studentClientUuid,
      required int halaqaId,
      required DateTime recordDate,
      required String payloadJson,
      Value<String> status,
      Value<int> attempts,
      Value<String?> lastError,
      Value<int?> serverRecordId,
      required DateTime clientCreatedAt,
      required DateTime updatedAt,
      Value<int> rowid,
    });
typedef $$PendingDailyRecordsTableUpdateCompanionBuilder =
    PendingDailyRecordsCompanion Function({
      Value<String> operationUuid,
      Value<int> studentId,
      Value<String?> studentClientUuid,
      Value<int> halaqaId,
      Value<DateTime> recordDate,
      Value<String> payloadJson,
      Value<String> status,
      Value<int> attempts,
      Value<String?> lastError,
      Value<int?> serverRecordId,
      Value<DateTime> clientCreatedAt,
      Value<DateTime> updatedAt,
      Value<int> rowid,
    });

class $$PendingDailyRecordsTableFilterComposer
    extends Composer<_$AppDatabase, $PendingDailyRecordsTable> {
  $$PendingDailyRecordsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get operationUuid => $composableBuilder(
    column: $table.operationUuid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get studentId => $composableBuilder(
    column: $table.studentId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get studentClientUuid => $composableBuilder(
    column: $table.studentClientUuid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get recordDate => $composableBuilder(
    column: $table.recordDate,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get attempts => $composableBuilder(
    column: $table.attempts,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get lastError => $composableBuilder(
    column: $table.lastError,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get serverRecordId => $composableBuilder(
    column: $table.serverRecordId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get clientCreatedAt => $composableBuilder(
    column: $table.clientCreatedAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$PendingDailyRecordsTableOrderingComposer
    extends Composer<_$AppDatabase, $PendingDailyRecordsTable> {
  $$PendingDailyRecordsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get operationUuid => $composableBuilder(
    column: $table.operationUuid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get studentId => $composableBuilder(
    column: $table.studentId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get studentClientUuid => $composableBuilder(
    column: $table.studentClientUuid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get recordDate => $composableBuilder(
    column: $table.recordDate,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get attempts => $composableBuilder(
    column: $table.attempts,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get lastError => $composableBuilder(
    column: $table.lastError,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get serverRecordId => $composableBuilder(
    column: $table.serverRecordId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get clientCreatedAt => $composableBuilder(
    column: $table.clientCreatedAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$PendingDailyRecordsTableAnnotationComposer
    extends Composer<_$AppDatabase, $PendingDailyRecordsTable> {
  $$PendingDailyRecordsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get operationUuid => $composableBuilder(
    column: $table.operationUuid,
    builder: (column) => column,
  );

  GeneratedColumn<int> get studentId =>
      $composableBuilder(column: $table.studentId, builder: (column) => column);

  GeneratedColumn<String> get studentClientUuid => $composableBuilder(
    column: $table.studentClientUuid,
    builder: (column) => column,
  );

  GeneratedColumn<int> get halaqaId =>
      $composableBuilder(column: $table.halaqaId, builder: (column) => column);

  GeneratedColumn<DateTime> get recordDate => $composableBuilder(
    column: $table.recordDate,
    builder: (column) => column,
  );

  GeneratedColumn<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<int> get attempts =>
      $composableBuilder(column: $table.attempts, builder: (column) => column);

  GeneratedColumn<String> get lastError =>
      $composableBuilder(column: $table.lastError, builder: (column) => column);

  GeneratedColumn<int> get serverRecordId => $composableBuilder(
    column: $table.serverRecordId,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get clientCreatedAt => $composableBuilder(
    column: $table.clientCreatedAt,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$PendingDailyRecordsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $PendingDailyRecordsTable,
          PendingDailyRecord,
          $$PendingDailyRecordsTableFilterComposer,
          $$PendingDailyRecordsTableOrderingComposer,
          $$PendingDailyRecordsTableAnnotationComposer,
          $$PendingDailyRecordsTableCreateCompanionBuilder,
          $$PendingDailyRecordsTableUpdateCompanionBuilder,
          (
            PendingDailyRecord,
            BaseReferences<
              _$AppDatabase,
              $PendingDailyRecordsTable,
              PendingDailyRecord
            >,
          ),
          PendingDailyRecord,
          PrefetchHooks Function()
        > {
  $$PendingDailyRecordsTableTableManager(
    _$AppDatabase db,
    $PendingDailyRecordsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$PendingDailyRecordsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$PendingDailyRecordsTableOrderingComposer(
                $db: db,
                $table: table,
              ),
          createComputedFieldComposer: () =>
              $$PendingDailyRecordsTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<String> operationUuid = const Value.absent(),
                Value<int> studentId = const Value.absent(),
                Value<String?> studentClientUuid = const Value.absent(),
                Value<int> halaqaId = const Value.absent(),
                Value<DateTime> recordDate = const Value.absent(),
                Value<String> payloadJson = const Value.absent(),
                Value<String> status = const Value.absent(),
                Value<int> attempts = const Value.absent(),
                Value<String?> lastError = const Value.absent(),
                Value<int?> serverRecordId = const Value.absent(),
                Value<DateTime> clientCreatedAt = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => PendingDailyRecordsCompanion(
                operationUuid: operationUuid,
                studentId: studentId,
                studentClientUuid: studentClientUuid,
                halaqaId: halaqaId,
                recordDate: recordDate,
                payloadJson: payloadJson,
                status: status,
                attempts: attempts,
                lastError: lastError,
                serverRecordId: serverRecordId,
                clientCreatedAt: clientCreatedAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String operationUuid,
                required int studentId,
                Value<String?> studentClientUuid = const Value.absent(),
                required int halaqaId,
                required DateTime recordDate,
                required String payloadJson,
                Value<String> status = const Value.absent(),
                Value<int> attempts = const Value.absent(),
                Value<String?> lastError = const Value.absent(),
                Value<int?> serverRecordId = const Value.absent(),
                required DateTime clientCreatedAt,
                required DateTime updatedAt,
                Value<int> rowid = const Value.absent(),
              }) => PendingDailyRecordsCompanion.insert(
                operationUuid: operationUuid,
                studentId: studentId,
                studentClientUuid: studentClientUuid,
                halaqaId: halaqaId,
                recordDate: recordDate,
                payloadJson: payloadJson,
                status: status,
                attempts: attempts,
                lastError: lastError,
                serverRecordId: serverRecordId,
                clientCreatedAt: clientCreatedAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$PendingDailyRecordsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $PendingDailyRecordsTable,
      PendingDailyRecord,
      $$PendingDailyRecordsTableFilterComposer,
      $$PendingDailyRecordsTableOrderingComposer,
      $$PendingDailyRecordsTableAnnotationComposer,
      $$PendingDailyRecordsTableCreateCompanionBuilder,
      $$PendingDailyRecordsTableUpdateCompanionBuilder,
      (
        PendingDailyRecord,
        BaseReferences<
          _$AppDatabase,
          $PendingDailyRecordsTable,
          PendingDailyRecord
        >,
      ),
      PendingDailyRecord,
      PrefetchHooks Function()
    >;
typedef $$CachedTeacherProfilesTableCreateCompanionBuilder =
    CachedTeacherProfilesCompanion Function({
      Value<int> id,
      required String payloadJson,
      required DateTime updatedAt,
    });
typedef $$CachedTeacherProfilesTableUpdateCompanionBuilder =
    CachedTeacherProfilesCompanion Function({
      Value<int> id,
      Value<String> payloadJson,
      Value<DateTime> updatedAt,
    });

class $$CachedTeacherProfilesTableFilterComposer
    extends Composer<_$AppDatabase, $CachedTeacherProfilesTable> {
  $$CachedTeacherProfilesTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CachedTeacherProfilesTableOrderingComposer
    extends Composer<_$AppDatabase, $CachedTeacherProfilesTable> {
  $$CachedTeacherProfilesTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CachedTeacherProfilesTableAnnotationComposer
    extends Composer<_$AppDatabase, $CachedTeacherProfilesTable> {
  $$CachedTeacherProfilesTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$CachedTeacherProfilesTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CachedTeacherProfilesTable,
          CachedTeacherProfile,
          $$CachedTeacherProfilesTableFilterComposer,
          $$CachedTeacherProfilesTableOrderingComposer,
          $$CachedTeacherProfilesTableAnnotationComposer,
          $$CachedTeacherProfilesTableCreateCompanionBuilder,
          $$CachedTeacherProfilesTableUpdateCompanionBuilder,
          (
            CachedTeacherProfile,
            BaseReferences<
              _$AppDatabase,
              $CachedTeacherProfilesTable,
              CachedTeacherProfile
            >,
          ),
          CachedTeacherProfile,
          PrefetchHooks Function()
        > {
  $$CachedTeacherProfilesTableTableManager(
    _$AppDatabase db,
    $CachedTeacherProfilesTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CachedTeacherProfilesTableFilterComposer(
                $db: db,
                $table: table,
              ),
          createOrderingComposer: () =>
              $$CachedTeacherProfilesTableOrderingComposer(
                $db: db,
                $table: table,
              ),
          createComputedFieldComposer: () =>
              $$CachedTeacherProfilesTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<String> payloadJson = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
              }) => CachedTeacherProfilesCompanion(
                id: id,
                payloadJson: payloadJson,
                updatedAt: updatedAt,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required String payloadJson,
                required DateTime updatedAt,
              }) => CachedTeacherProfilesCompanion.insert(
                id: id,
                payloadJson: payloadJson,
                updatedAt: updatedAt,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CachedTeacherProfilesTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CachedTeacherProfilesTable,
      CachedTeacherProfile,
      $$CachedTeacherProfilesTableFilterComposer,
      $$CachedTeacherProfilesTableOrderingComposer,
      $$CachedTeacherProfilesTableAnnotationComposer,
      $$CachedTeacherProfilesTableCreateCompanionBuilder,
      $$CachedTeacherProfilesTableUpdateCompanionBuilder,
      (
        CachedTeacherProfile,
        BaseReferences<
          _$AppDatabase,
          $CachedTeacherProfilesTable,
          CachedTeacherProfile
        >,
      ),
      CachedTeacherProfile,
      PrefetchHooks Function()
    >;
typedef $$CachedStudentProfilesTableCreateCompanionBuilder =
    CachedStudentProfilesCompanion Function({
      Value<int> studentId,
      required String payloadJson,
      required DateTime updatedAt,
    });
typedef $$CachedStudentProfilesTableUpdateCompanionBuilder =
    CachedStudentProfilesCompanion Function({
      Value<int> studentId,
      Value<String> payloadJson,
      Value<DateTime> updatedAt,
    });

class $$CachedStudentProfilesTableFilterComposer
    extends Composer<_$AppDatabase, $CachedStudentProfilesTable> {
  $$CachedStudentProfilesTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get studentId => $composableBuilder(
    column: $table.studentId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CachedStudentProfilesTableOrderingComposer
    extends Composer<_$AppDatabase, $CachedStudentProfilesTable> {
  $$CachedStudentProfilesTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get studentId => $composableBuilder(
    column: $table.studentId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CachedStudentProfilesTableAnnotationComposer
    extends Composer<_$AppDatabase, $CachedStudentProfilesTable> {
  $$CachedStudentProfilesTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get studentId =>
      $composableBuilder(column: $table.studentId, builder: (column) => column);

  GeneratedColumn<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$CachedStudentProfilesTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CachedStudentProfilesTable,
          CachedStudentProfile,
          $$CachedStudentProfilesTableFilterComposer,
          $$CachedStudentProfilesTableOrderingComposer,
          $$CachedStudentProfilesTableAnnotationComposer,
          $$CachedStudentProfilesTableCreateCompanionBuilder,
          $$CachedStudentProfilesTableUpdateCompanionBuilder,
          (
            CachedStudentProfile,
            BaseReferences<
              _$AppDatabase,
              $CachedStudentProfilesTable,
              CachedStudentProfile
            >,
          ),
          CachedStudentProfile,
          PrefetchHooks Function()
        > {
  $$CachedStudentProfilesTableTableManager(
    _$AppDatabase db,
    $CachedStudentProfilesTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CachedStudentProfilesTableFilterComposer(
                $db: db,
                $table: table,
              ),
          createOrderingComposer: () =>
              $$CachedStudentProfilesTableOrderingComposer(
                $db: db,
                $table: table,
              ),
          createComputedFieldComposer: () =>
              $$CachedStudentProfilesTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<int> studentId = const Value.absent(),
                Value<String> payloadJson = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
              }) => CachedStudentProfilesCompanion(
                studentId: studentId,
                payloadJson: payloadJson,
                updatedAt: updatedAt,
              ),
          createCompanionCallback:
              ({
                Value<int> studentId = const Value.absent(),
                required String payloadJson,
                required DateTime updatedAt,
              }) => CachedStudentProfilesCompanion.insert(
                studentId: studentId,
                payloadJson: payloadJson,
                updatedAt: updatedAt,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CachedStudentProfilesTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CachedStudentProfilesTable,
      CachedStudentProfile,
      $$CachedStudentProfilesTableFilterComposer,
      $$CachedStudentProfilesTableOrderingComposer,
      $$CachedStudentProfilesTableAnnotationComposer,
      $$CachedStudentProfilesTableCreateCompanionBuilder,
      $$CachedStudentProfilesTableUpdateCompanionBuilder,
      (
        CachedStudentProfile,
        BaseReferences<
          _$AppDatabase,
          $CachedStudentProfilesTable,
          CachedStudentProfile
        >,
      ),
      CachedStudentProfile,
      PrefetchHooks Function()
    >;
typedef $$PendingStudentOperationsTableCreateCompanionBuilder =
    PendingStudentOperationsCompanion Function({
      required String operationUuid,
      required String operationType,
      Value<int?> studentId,
      Value<String?> clientStudentUuid,
      required int halaqaId,
      required String payloadJson,
      Value<String> status,
      Value<int> attempts,
      Value<String?> lastError,
      Value<int?> serverStudentId,
      required DateTime clientCreatedAt,
      required DateTime updatedAt,
      Value<int> rowid,
    });
typedef $$PendingStudentOperationsTableUpdateCompanionBuilder =
    PendingStudentOperationsCompanion Function({
      Value<String> operationUuid,
      Value<String> operationType,
      Value<int?> studentId,
      Value<String?> clientStudentUuid,
      Value<int> halaqaId,
      Value<String> payloadJson,
      Value<String> status,
      Value<int> attempts,
      Value<String?> lastError,
      Value<int?> serverStudentId,
      Value<DateTime> clientCreatedAt,
      Value<DateTime> updatedAt,
      Value<int> rowid,
    });

class $$PendingStudentOperationsTableFilterComposer
    extends Composer<_$AppDatabase, $PendingStudentOperationsTable> {
  $$PendingStudentOperationsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get operationUuid => $composableBuilder(
    column: $table.operationUuid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get operationType => $composableBuilder(
    column: $table.operationType,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get studentId => $composableBuilder(
    column: $table.studentId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get clientStudentUuid => $composableBuilder(
    column: $table.clientStudentUuid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get attempts => $composableBuilder(
    column: $table.attempts,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get lastError => $composableBuilder(
    column: $table.lastError,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get serverStudentId => $composableBuilder(
    column: $table.serverStudentId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get clientCreatedAt => $composableBuilder(
    column: $table.clientCreatedAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$PendingStudentOperationsTableOrderingComposer
    extends Composer<_$AppDatabase, $PendingStudentOperationsTable> {
  $$PendingStudentOperationsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get operationUuid => $composableBuilder(
    column: $table.operationUuid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get operationType => $composableBuilder(
    column: $table.operationType,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get studentId => $composableBuilder(
    column: $table.studentId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get clientStudentUuid => $composableBuilder(
    column: $table.clientStudentUuid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get attempts => $composableBuilder(
    column: $table.attempts,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get lastError => $composableBuilder(
    column: $table.lastError,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get serverStudentId => $composableBuilder(
    column: $table.serverStudentId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get clientCreatedAt => $composableBuilder(
    column: $table.clientCreatedAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$PendingStudentOperationsTableAnnotationComposer
    extends Composer<_$AppDatabase, $PendingStudentOperationsTable> {
  $$PendingStudentOperationsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get operationUuid => $composableBuilder(
    column: $table.operationUuid,
    builder: (column) => column,
  );

  GeneratedColumn<String> get operationType => $composableBuilder(
    column: $table.operationType,
    builder: (column) => column,
  );

  GeneratedColumn<int> get studentId =>
      $composableBuilder(column: $table.studentId, builder: (column) => column);

  GeneratedColumn<String> get clientStudentUuid => $composableBuilder(
    column: $table.clientStudentUuid,
    builder: (column) => column,
  );

  GeneratedColumn<int> get halaqaId =>
      $composableBuilder(column: $table.halaqaId, builder: (column) => column);

  GeneratedColumn<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<int> get attempts =>
      $composableBuilder(column: $table.attempts, builder: (column) => column);

  GeneratedColumn<String> get lastError =>
      $composableBuilder(column: $table.lastError, builder: (column) => column);

  GeneratedColumn<int> get serverStudentId => $composableBuilder(
    column: $table.serverStudentId,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get clientCreatedAt => $composableBuilder(
    column: $table.clientCreatedAt,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$PendingStudentOperationsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $PendingStudentOperationsTable,
          PendingStudentOperation,
          $$PendingStudentOperationsTableFilterComposer,
          $$PendingStudentOperationsTableOrderingComposer,
          $$PendingStudentOperationsTableAnnotationComposer,
          $$PendingStudentOperationsTableCreateCompanionBuilder,
          $$PendingStudentOperationsTableUpdateCompanionBuilder,
          (
            PendingStudentOperation,
            BaseReferences<
              _$AppDatabase,
              $PendingStudentOperationsTable,
              PendingStudentOperation
            >,
          ),
          PendingStudentOperation,
          PrefetchHooks Function()
        > {
  $$PendingStudentOperationsTableTableManager(
    _$AppDatabase db,
    $PendingStudentOperationsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$PendingStudentOperationsTableFilterComposer(
                $db: db,
                $table: table,
              ),
          createOrderingComposer: () =>
              $$PendingStudentOperationsTableOrderingComposer(
                $db: db,
                $table: table,
              ),
          createComputedFieldComposer: () =>
              $$PendingStudentOperationsTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<String> operationUuid = const Value.absent(),
                Value<String> operationType = const Value.absent(),
                Value<int?> studentId = const Value.absent(),
                Value<String?> clientStudentUuid = const Value.absent(),
                Value<int> halaqaId = const Value.absent(),
                Value<String> payloadJson = const Value.absent(),
                Value<String> status = const Value.absent(),
                Value<int> attempts = const Value.absent(),
                Value<String?> lastError = const Value.absent(),
                Value<int?> serverStudentId = const Value.absent(),
                Value<DateTime> clientCreatedAt = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => PendingStudentOperationsCompanion(
                operationUuid: operationUuid,
                operationType: operationType,
                studentId: studentId,
                clientStudentUuid: clientStudentUuid,
                halaqaId: halaqaId,
                payloadJson: payloadJson,
                status: status,
                attempts: attempts,
                lastError: lastError,
                serverStudentId: serverStudentId,
                clientCreatedAt: clientCreatedAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String operationUuid,
                required String operationType,
                Value<int?> studentId = const Value.absent(),
                Value<String?> clientStudentUuid = const Value.absent(),
                required int halaqaId,
                required String payloadJson,
                Value<String> status = const Value.absent(),
                Value<int> attempts = const Value.absent(),
                Value<String?> lastError = const Value.absent(),
                Value<int?> serverStudentId = const Value.absent(),
                required DateTime clientCreatedAt,
                required DateTime updatedAt,
                Value<int> rowid = const Value.absent(),
              }) => PendingStudentOperationsCompanion.insert(
                operationUuid: operationUuid,
                operationType: operationType,
                studentId: studentId,
                clientStudentUuid: clientStudentUuid,
                halaqaId: halaqaId,
                payloadJson: payloadJson,
                status: status,
                attempts: attempts,
                lastError: lastError,
                serverStudentId: serverStudentId,
                clientCreatedAt: clientCreatedAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$PendingStudentOperationsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $PendingStudentOperationsTable,
      PendingStudentOperation,
      $$PendingStudentOperationsTableFilterComposer,
      $$PendingStudentOperationsTableOrderingComposer,
      $$PendingStudentOperationsTableAnnotationComposer,
      $$PendingStudentOperationsTableCreateCompanionBuilder,
      $$PendingStudentOperationsTableUpdateCompanionBuilder,
      (
        PendingStudentOperation,
        BaseReferences<
          _$AppDatabase,
          $PendingStudentOperationsTable,
          PendingStudentOperation
        >,
      ),
      PendingStudentOperation,
      PrefetchHooks Function()
    >;
typedef $$LocalStudentDraftsTableCreateCompanionBuilder =
    LocalStudentDraftsCompanion Function({
      required String clientUuid,
      required int halaqaId,
      required String payloadJson,
      required DateTime createdAt,
      required DateTime updatedAt,
      Value<int> rowid,
    });
typedef $$LocalStudentDraftsTableUpdateCompanionBuilder =
    LocalStudentDraftsCompanion Function({
      Value<String> clientUuid,
      Value<int> halaqaId,
      Value<String> payloadJson,
      Value<DateTime> createdAt,
      Value<DateTime> updatedAt,
      Value<int> rowid,
    });

class $$LocalStudentDraftsTableFilterComposer
    extends Composer<_$AppDatabase, $LocalStudentDraftsTable> {
  $$LocalStudentDraftsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get clientUuid => $composableBuilder(
    column: $table.clientUuid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get createdAt => $composableBuilder(
    column: $table.createdAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$LocalStudentDraftsTableOrderingComposer
    extends Composer<_$AppDatabase, $LocalStudentDraftsTable> {
  $$LocalStudentDraftsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get clientUuid => $composableBuilder(
    column: $table.clientUuid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get halaqaId => $composableBuilder(
    column: $table.halaqaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get createdAt => $composableBuilder(
    column: $table.createdAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$LocalStudentDraftsTableAnnotationComposer
    extends Composer<_$AppDatabase, $LocalStudentDraftsTable> {
  $$LocalStudentDraftsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get clientUuid => $composableBuilder(
    column: $table.clientUuid,
    builder: (column) => column,
  );

  GeneratedColumn<int> get halaqaId =>
      $composableBuilder(column: $table.halaqaId, builder: (column) => column);

  GeneratedColumn<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get createdAt =>
      $composableBuilder(column: $table.createdAt, builder: (column) => column);

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$LocalStudentDraftsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $LocalStudentDraftsTable,
          LocalStudentDraft,
          $$LocalStudentDraftsTableFilterComposer,
          $$LocalStudentDraftsTableOrderingComposer,
          $$LocalStudentDraftsTableAnnotationComposer,
          $$LocalStudentDraftsTableCreateCompanionBuilder,
          $$LocalStudentDraftsTableUpdateCompanionBuilder,
          (
            LocalStudentDraft,
            BaseReferences<
              _$AppDatabase,
              $LocalStudentDraftsTable,
              LocalStudentDraft
            >,
          ),
          LocalStudentDraft,
          PrefetchHooks Function()
        > {
  $$LocalStudentDraftsTableTableManager(
    _$AppDatabase db,
    $LocalStudentDraftsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalStudentDraftsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalStudentDraftsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalStudentDraftsTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<String> clientUuid = const Value.absent(),
                Value<int> halaqaId = const Value.absent(),
                Value<String> payloadJson = const Value.absent(),
                Value<DateTime> createdAt = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalStudentDraftsCompanion(
                clientUuid: clientUuid,
                halaqaId: halaqaId,
                payloadJson: payloadJson,
                createdAt: createdAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String clientUuid,
                required int halaqaId,
                required String payloadJson,
                required DateTime createdAt,
                required DateTime updatedAt,
                Value<int> rowid = const Value.absent(),
              }) => LocalStudentDraftsCompanion.insert(
                clientUuid: clientUuid,
                halaqaId: halaqaId,
                payloadJson: payloadJson,
                createdAt: createdAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$LocalStudentDraftsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $LocalStudentDraftsTable,
      LocalStudentDraft,
      $$LocalStudentDraftsTableFilterComposer,
      $$LocalStudentDraftsTableOrderingComposer,
      $$LocalStudentDraftsTableAnnotationComposer,
      $$LocalStudentDraftsTableCreateCompanionBuilder,
      $$LocalStudentDraftsTableUpdateCompanionBuilder,
      (
        LocalStudentDraft,
        BaseReferences<
          _$AppDatabase,
          $LocalStudentDraftsTable,
          LocalStudentDraft
        >,
      ),
      LocalStudentDraft,
      PrefetchHooks Function()
    >;

class $AppDatabaseManager {
  final _$AppDatabase _db;
  $AppDatabaseManager(this._db);
  $$AppSettingsTableTableManager get appSettings =>
      $$AppSettingsTableTableManager(_db, _db.appSettings);
  $$CachedHalaqasTableTableManager get cachedHalaqas =>
      $$CachedHalaqasTableTableManager(_db, _db.cachedHalaqas);
  $$CachedStudentsTableTableManager get cachedStudents =>
      $$CachedStudentsTableTableManager(_db, _db.cachedStudents);
  $$CachedSurahsTableTableManager get cachedSurahs =>
      $$CachedSurahsTableTableManager(_db, _db.cachedSurahs);
  $$CachedAyahsTableTableManager get cachedAyahs =>
      $$CachedAyahsTableTableManager(_db, _db.cachedAyahs);
  $$PendingDailyRecordsTableTableManager get pendingDailyRecords =>
      $$PendingDailyRecordsTableTableManager(_db, _db.pendingDailyRecords);
  $$CachedTeacherProfilesTableTableManager get cachedTeacherProfiles =>
      $$CachedTeacherProfilesTableTableManager(_db, _db.cachedTeacherProfiles);
  $$CachedStudentProfilesTableTableManager get cachedStudentProfiles =>
      $$CachedStudentProfilesTableTableManager(_db, _db.cachedStudentProfiles);
  $$PendingStudentOperationsTableTableManager get pendingStudentOperations =>
      $$PendingStudentOperationsTableTableManager(
        _db,
        _db.pendingStudentOperations,
      );
  $$LocalStudentDraftsTableTableManager get localStudentDrafts =>
      $$LocalStudentDraftsTableTableManager(_db, _db.localStudentDrafts);
}
